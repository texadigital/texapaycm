import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity, FlatList, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { setNameEnquiryRef } from '../../lib/transferState';
import BankPickerModal, { BankItem } from '../../components/BankPickerModal';

export default function TransferVerifyScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [bankCode, setBankCode] = React.useState('');
  const [bankName, setBankName] = React.useState('');
  const [account, setAccount] = React.useState('');
  const [ne, setNe] = React.useState<{ success?: boolean; accountName?: string; reference?: string; message?: string } | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [suggestBusy, setSuggestBusy] = React.useState(false);
  const [suggestions, setSuggestions] = React.useState<Array<{ bankCode: string; name: string }>>([]);
  const [cooldownUntil, setCooldownUntil] = React.useState<number>(0);
  const [cooldownLeft, setCooldownLeft] = React.useState<number>(0);
  const [lastNEKey, setLastNEKey] = React.useState<string>('');
  const [verifiedKey, setVerifiedKey] = React.useState<string>('');
  const [statusMsg, setStatusMsg] = React.useState<string>('');
  const [search, setSearch] = React.useState('');
  const [recents, setRecents] = React.useState<Array<{ bankCode: string; bankName?: string; accountNumber: string; accountName?: string }>>([]);
  const debounceRef = React.useRef<any>(null);
  const [pickerOpen, setPickerOpen] = React.useState(false);
  const [banksAll, setBanksAll] = React.useState<BankItem[]>([]);
  const [banksFrequent, setBanksFrequent] = React.useState<BankItem[]>([]);

  React.useEffect(() => {
    const acct = account.trim();
    if (acct.length < 6) { setSuggestions([]); return; }
    let cancelled = false;
    (async () => {
      try {
        setSuggestBusy(true);
        const res = await http.post('/api/mobile/banks/suggest', { accountNumber: acct });
        if (cancelled) return;
        const data = res?.data || {};
        const list = (data.suggestions as Array<{ bankCode: string; name: string }> | undefined) || [];
        setSuggestions(list);
        const bank = data.bank as { bankCode?: string; name?: string } | undefined;
        if (data.resolved && bank?.bankCode) {
          setBankCode(bank.bankCode);
          setBankName(bank.name || '');
        }
      } catch {
        if (!cancelled) setSuggestions([]);
      } finally {
        if (!cancelled) setSuggestBusy(false);
      }
    })();
    return () => { cancelled = true; };
  }, [account]);

  async function nameEnquiry() {
    const acct = account.trim();
    if (!bankCode || acct.length < 10) return;
    try {
      setBusy(true);
      setError(null);
      setNe(null);
      const res = await http.post('/api/mobile/transfers/name-enquiry', { bankCode, accountNumber: acct });
      const d = res?.data || {};
      setNe(d);
      if (d?.reference) {
        try { setNameEnquiryRef(bankCode, acct, d.reference); } catch {}
      }
      if (d?.accountName) {
        const key = bankCode + ':' + acct;
        setVerifiedKey(key);
        setStatusMsg(`Verified ${d.accountName}`);
      }
    } catch (e: any) {
      const msg = e?.response?.data?.message || e.message || 'Failed to verify';
      setError(msg);
      const status = e?.response?.status;
      if (status === 429 || /too many/i.test(String(msg))) {
        const ra = Math.max(5, Math.min(120, Number(e?.response?.headers?.['retry-after']) || Number(e?.response?.data?.retryAfterSeconds) || 15));
        setCooldownUntil(Date.now() + ra * 1000);
      }
    } finally {
      setBusy(false);
    }
  }

  // Auto NE when bank and 10-digit account are ready
  React.useEffect(() => {
    const acct = account.trim();
    const ready = !!bankCode && acct.length >= 10;
    if (!ready) return;
    const key = bankCode + ':' + acct;
    if (key === lastNEKey) return;
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      if (Date.now() < cooldownUntil) return;
      setLastNEKey(key);
      nameEnquiry();
    }, 450);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
  }, [bankCode, account, lastNEKey, cooldownUntil]);

  // Cooldown countdown tick
  React.useEffect(() => {
    if (cooldownUntil <= Date.now()) { setCooldownLeft(0); return; }
    const tick = () => setCooldownLeft(Math.max(0, Math.ceil((cooldownUntil - Date.now())/1000)));
    tick();
    const id = setInterval(tick, 500);
    return () => clearInterval(id);
  }, [cooldownUntil]);

  // Load recents from API (try multiple endpoints/shapes for old accounts)
  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      async function fetchAny(): Promise<any[]> {
        const shapes: Array<() => Promise<any>> = [
          async () => (await http.get('/api/mobile/transfers', { params: { perPage: 20 } })).data,
          async () => (await http.get('/api/mobile/transfers/recents')).data,
          async () => (await http.get('/api/mobile/recipients')).data,
        ];
        for (const fn of shapes) {
          try {
            const d = await fn();
            const arr = Array.isArray(d?.data) ? d.data : Array.isArray(d?.items) ? d.items : (Array.isArray(d) ? d : []);
            if (arr.length) return arr;
          } catch { /* continue */ }
        }
        return [];
      }
      try {
        const raw = await fetchAny();
        if (cancelled) return;
        // Normalize possible shapes
        const norm = raw.map((it: any) => ({
          bankCode: it.bankCode || it.bank_code || it.bank || '',
          bankName: it.bankName || it.bank_name || it.bank || '',
          accountNumber: it.accountNumber || it.account_number || it.account || '',
          accountName: it.accountName || it.account_name || it.name || it.recipientName || '',
          updatedAt: new Date(it.updatedAt || it.updated_at || it.createdAt || it.created_at || 0).getTime(),
        })).filter((r: any) => r.bankCode && r.accountNumber);

        // Sort newest first
        norm.sort((a: any, b: any) => (b.updatedAt || 0) - (a.updatedAt || 0));

        // Dedupe by bankCode:accountNumber
        const map = new Map<string, { bankCode: string; bankName?: string; accountNumber: string; accountName?: string }>();
        for (const it of norm) {
          const key = `${it.bankCode}:${it.accountNumber}`;
          const prev = map.get(key);
          if (!prev) {
            map.set(key, { bankCode: it.bankCode, bankName: it.bankName, accountNumber: it.accountNumber, accountName: it.accountName });
          } else {
            map.set(key, {
              bankCode: it.bankCode,
              accountNumber: it.accountNumber,
              accountName: prev.accountName || it.accountName,
              bankName: prev.bankName || it.bankName,
            });
          }
        }
        const deduped = Array.from(map.values());
        setRecents(deduped);
        // Build frequent bank list from recents
        const bankMap = new Map<string, string>();
        for (const r of deduped) { if (r.bankCode) bankMap.set(r.bankCode, r.bankName || r.bankCode); }
        setBanksFrequent(Array.from(bankMap.entries()).slice(0,6).map(([code, name]) => ({ bankCode: code, name })));
      } catch {}
    })();
    return () => { cancelled = true; };
  }, []);

  // Load bank directory for picker (best-effort)
  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await http.get('/api/mobile/banks/directory').catch(() => http.get('/api/mobile/banks'));
        const rows = Array.isArray(res?.data?.banks) ? res.data.banks : (Array.isArray(res?.data) ? res.data : []);
        if (!cancelled) {
          const all: BankItem[] = rows.map((b: any) => ({ bankCode: b.bankCode || b.code || b.id, name: b.name || b.label || '' })).filter((b: BankItem) => b.bankCode && b.name);
          setBanksAll(all);
        }
      } catch {}
    })();
    return () => { cancelled = true; };
  }, []);

  function goNext() {
    if (!ne?.accountName || !bankCode || account.trim().length < 10) return;
    nav.navigate('TransferQuote', { recipient: { bankCode, bankName, account: account.trim(), accountName: ne.accountName } });
  }

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <View style={styles.container}>
        <View style={styles.headerRow}>
          <Text style={styles.headerTitle}>Transfer To Bank Account</Text>
          <TouchableOpacity onPress={() => nav.navigate('TransfersHistory')}><Text style={styles.linkText}>History</Text></TouchableOpacity>
        </View>

        {!!error && <Text style={styles.errorText}>{error}{cooldownLeft>0?` (${cooldownLeft}s)`:''}</Text>}

        {/* Recipient Account card */}
        <View style={styles.card}>
          <Text style={styles.sectionLabel}>Recipient Account</Text>
          <View style={styles.inputBox}>
            <TextInput
              value={account}
              onChangeText={(t) => setAccount(t.replace(/\D+/g, '').slice(0, 10))}
              keyboardType="number-pad"
              placeholder="Enter 10 digits Account Number"
              placeholderTextColor="#9CA3AF"
              style={styles.inputField}
            />
          </View>
            {/* Inline verification status */}
            <View style={{ marginTop: 6 }}>
              {busy && (
                <View style={styles.inlineBadge}><ActivityIndicator size="small" color="#111827" /><Text style={styles.inlineBadgeText}>Verifying…</Text></View>
              )}
              {!busy && ne?.accountName && ((bankCode+":"+account.trim())===verifiedKey) && (
                <View style={[styles.inlineBadge, { backgroundColor: '#E6EEFF', borderColor: '#C7D2FE' }]}>
                  <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: '#1543A6' }} />
                  <Text style={[styles.inlineBadgeText, { color: '#1543A6' }]}>{ne.accountName}</Text>
                </View>
              )}
              {!busy && !ne?.accountName && !!bankCode && account.trim().length>=10 && cooldownLeft===0 && (
                <TouchableOpacity onPress={() => { setLastNEKey(bankCode+":"+account.trim()); nameEnquiry(); }}>
                  <Text style={{ fontSize: 12, textDecorationLine: 'underline', color: '#374151' }}>Verify now</Text>
                </TouchableOpacity>
              )}
            </View>
          
          {/* Bank row as disabled-style input */}
          <TouchableOpacity style={[styles.inputBox, styles.bankInputBox]} onPress={() => setPickerOpen(true)}>
            <Text
              style={[
                styles.bankRowText,
                !bankName ? { color: '#9CA3AF', textAlign: 'center', width: '100%' } : { textAlign: 'left' },
              ]}
              numberOfLines={1}
            >
              {bankName || 'Select Bank'}
            </Text>
          </TouchableOpacity>
        </View>

        {/* Recents Card with Tabs */}
        <View style={styles.recentsCard}>
          <View style={styles.tabsRow}>
            <TouchableOpacity><Text style={[styles.tabText, styles.tabActive]}>Recents</Text></TouchableOpacity>
            <TouchableOpacity><Text style={styles.tabText}>Favourites</Text></TouchableOpacity>
            <View style={{ flex: 1 }} />
            <TouchableOpacity onPress={() => { /* open search pane later */ }}><Text style={styles.searchIcon}>⌕</Text></TouchableOpacity>
          </View>
          <View style={styles.tabUnderline} />
          <View>
            {recents.filter(r => {
              const q = search.trim().toLowerCase();
              if (!q) return true;
              return (r.accountName||'').toLowerCase().includes(q) || (r.bankName||'').toLowerCase().includes(q) || r.accountNumber.includes(q);
            }).slice(0, 3).map((r) => (
              <TouchableOpacity key={r.bankCode+":"+r.accountNumber} style={styles.recentRow} onPress={() => {
                setBankCode(r.bankCode); setBankName(r.bankName || ''); setAccount(r.accountNumber);
                // Prime verified pill if we have name, still trigger a fresh NE for up-to-date status
                if (r.accountName) {
                  const k = r.bankCode+":"+r.accountNumber; setVerifiedKey(k);
                  setNe({ accountName: r.accountName, success: true });
                } else {
                  setNe(null);
                }
                setTimeout(() => nameEnquiry(), 0);
              }}>
                <View style={{ flex: 1 }}>
                  <Text style={{ fontWeight: '600', color: '#0B0F1A' }} numberOfLines={1}>{r.accountName || r.accountNumber}</Text>
                  <Text style={{ fontSize: 12, color: '#6B7280' }}>{(r.accountNumber||'').slice(0)}  {r.bankName || r.bankCode}</Text>
                </View>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                  <View style={styles.bankAvatar}><Text style={styles.bankAvatarText}>{(r.bankName||'').slice(0,1).toUpperCase()}</Text></View>
                  <Text style={{ color: '#9CA3AF' }}>›</Text>
                </View>
              </TouchableOpacity>
            ))}
            {recents.length === 0 && (
              <Text style={{ color: '#6B7280', fontSize: 12, padding: 12 }}>No recipients.</Text>
            )}
            <TouchableOpacity style={styles.viewAllBtn}><Text style={styles.viewAllText}>View All</Text></TouchableOpacity>
          </View>
        </View>

        <TouchableOpacity style={[styles.nextBtn, (!(ne?.accountName && (bankCode+":"+account.trim())===verifiedKey)) && styles.nextBtnDisabled]} disabled={!(ne?.accountName && (bankCode+":"+account.trim())===verifiedKey)} onPress={goNext}>
          <Text style={styles.nextBtnText}>Next</Text>
        </TouchableOpacity>

        <BankPickerModal
          open={pickerOpen}
          onClose={() => setPickerOpen(false)}
          onSelect={(b) => { setBankCode(b.bankCode); setBankName(b.name); setPickerOpen(false); }}
          matched={suggestions.map(s => ({ bankCode: s.bankCode, name: s.name }))}
          frequent={banksFrequent}
          all={banksAll}
        />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  headerTitle: { color: '#0B0F1A', fontSize: 16, fontWeight: '700' },
  linkText: { color: '#111827', textDecorationLine: 'underline' },
  card: { backgroundColor: '#FFFFFF', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 12 },
  sectionLabel: { color: '#0B0F1A', fontWeight: '600', marginBottom: 8 },
  inputBox: { borderWidth: 0, borderColor: 'transparent', borderRadius: 8, paddingHorizontal: 12, height: 48, backgroundColor: '#F3F4F6', justifyContent: 'center' },
  inputField: { color: '#0B0F1A', fontSize: 16 },
  bankInputBox: { marginTop: 8 },
  bankRowText: { flex: 1, marginLeft: 0, color: '#111827', fontSize: 16 },
  bankAvatar: { width: 28, height: 28, borderRadius: 14, backgroundColor: '#F3F4F6', alignItems: 'center', justifyContent: 'center' },
  bankAvatarText: { fontSize: 11, color: '#111827' },
  inlineBadge: { flexDirection: 'row', alignItems: 'center', gap: 6, borderWidth: 1, borderColor: '#E5E7EB', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4, alignSelf: 'flex-start' },
  inlineBadgeText: { fontSize: 12, color: '#111827' },
  hintText: { marginTop: 6, color: '#6B7280', fontSize: 12 },
  recentsCard: { backgroundColor: '#FFFFFF', borderRadius: 12, padding: 12, marginTop: 12 },
  tabsRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  tabText: { color: '#6B7280', marginRight: 16 },
  tabActive: { color: '#0B0F1A', fontWeight: '700' },
  tabUnderline: { height: 2, width: 60, backgroundColor: '#1543A6', marginTop: -6, marginBottom: 6 },
  searchIcon: { color: '#111827', fontSize: 18 },
  recentRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 12, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: '#E8ECF8' },
  viewAllBtn: { alignItems: 'center', paddingVertical: 8 },
  viewAllText: { color: '#6B7280' },
  nextBtn: { marginTop: 16, height: 52, borderRadius: 26, backgroundColor: '#1543A6', alignItems: 'center', justifyContent: 'center' },
  nextBtnText: { color: '#FFFFFF', fontWeight: '600', fontSize: 16 },
  nextBtnDisabled: { backgroundColor: '#C9D7FF' },
  errorText: { color: '#991B1B', marginBottom: 8 },
});
