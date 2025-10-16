import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TouchableOpacity, TextInput } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { getSelectedQuote, getNameEnquiryRef } from '../../lib/transferState';

export default function TransferConfirmScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<any>();
  const { quoteId } = route.params || {};

  const [bankCode, setBankCode] = React.useState('');
  const [bankName, setBankName] = React.useState('');
  const [account, setAccount] = React.useState('');
  const [accountName, setAccountName] = React.useState('');
  const [msisdn, setMsisdn] = React.useState('');
  const [quote, setQuote] = React.useState<any>(null);
  const [ttlSec, setTtlSec] = React.useState(0);
  const [error, setError] = React.useState<string | null>(null);
  const [pending, setPending] = React.useState(false);
  const [cooldownUntil, setCooldownUntil] = React.useState(0);
  const [cooldownLeft, setCooldownLeft] = React.useState(0);

  React.useEffect(() => {
    const sel = getSelectedQuote();
    if (!sel?.recipient || !sel?.quote) {
      nav.replace('TransferVerify');
      return;
    }
    setBankCode(sel.recipient.bankCode);
    setBankName(sel.recipient.bankName);
    setAccount(sel.recipient.account);
    setAccountName(sel.recipient.accountName);
    setQuote(sel.quote);
  }, [nav]);

  React.useEffect(() => {
    if (!quote?.expiresAt) { setTtlSec(0); return; }
    const expires = new Date(quote.expiresAt).getTime();
    const tick = () => setTtlSec(Math.max(0, Math.floor((expires - Date.now()) / 1000)));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quote?.expiresAt]);

  React.useEffect(() => {
    if (cooldownUntil <= Date.now()) { setCooldownLeft(0); return; }
    const tick = () => setCooldownLeft(Math.max(0, Math.ceil((cooldownUntil - Date.now())/1000)));
    tick();
    const id = setInterval(tick, 500);
    return () => clearInterval(id);
  }, [cooldownUntil]);

  function validateCameroonPhone(p: string): { valid: boolean; normalized?: string; error?: string } {
    const digits = p.replace(/\D+/g, '');
    if (!/^237[236789]\d{7,8}$/.test(digits)) {
      return { valid: false, error: 'Enter MSISDN like 2376XXXXXXXX' };
    }
    return { valid: true, normalized: digits };
  }

  async function doConfirm() {
    if (!quote?.id) return;
    const v = validateCameroonPhone(msisdn);
    if (!v.valid) { setError(v.error || 'Invalid phone'); return; }
    if (Date.now() < cooldownUntil) {
      const left = Math.max(0, Math.ceil((cooldownUntil - Date.now())/1000));
      setError(`Please wait ${left}s before trying again.`);
      return;
    }
    try {
      setPending(true);
      setError(null);
      const neRef = getNameEnquiryRef(bankCode, account) || undefined;
      const res = await http.post('/api/mobile/transfers/confirm', {
        quoteId: Number(quoteId || quote.id),
        bankCode: bankCode,
        accountNumber: account,
        msisdn: v.normalized,
        accountName: accountName || undefined,
        nameEnquiryRef: neRef,
      });
      const data = res?.data || {};
      const transferId = data?.transfer?.id;
      if (transferId) {
        nav.replace('TransferProcessing', { transferId });
      } else {
        setError('Unexpected response');
      }
    } catch (e: any) {
      if (e?.response?.status === 429) {
        const raHdr = Number(e?.response?.headers?.['retry-after']) || Number(e?.response?.data?.retryAfterSeconds);
        const ra = Math.max(5, Math.min(120, raHdr || 12));
        const until = Date.now() + ra * 1000;
        setCooldownUntil(until);
        setError(`Please wait ${ra}s before trying again.`);
      } else {
        const d = e?.response?.data || {};
        setError(d?.message || e.message || 'Failed to confirm');
      }
    } finally {
      setPending(false);
    }
  }

  const receiveNgn = quote ? (quote.receiveNgnMinor / 100) : 0;
  const nf = React.useMemo(() => new Intl.NumberFormat('en-NG'), []);
  const ngnFmt = React.useMemo(() => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 }), []);

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <View style={styles.container}>
        <View style={styles.headerRow}>
          <Text style={styles.headerTitle}>Transfer To Bank Account</Text>
          <TouchableOpacity onPress={() => nav.navigate('TransfersHistory')}><Text style={styles.linkText}>History</Text></TouchableOpacity>
        </View>
        {!!error && <Text style={styles.errBox}>{error}{cooldownLeft>0?` (${cooldownLeft}s)`:''}</Text>}

        <View style={styles.recipientCard}>
          <View style={styles.recAvatar}><Text style={styles.recAvatarText}>{(bankName || '•').slice(0,1).toUpperCase()}</Text></View>
          <View style={{ flex: 1, minWidth: 0 }}>
            <Text style={styles.recName} numberOfLines={1}>{accountName || 'Recipient'}</Text>
            <Text style={styles.recAcct} numberOfLines={1}>{account}</Text>
            <Text style={styles.recBank} numberOfLines={1}>{bankName}</Text>
          </View>
        </View>

        <View style={styles.card}>
          <View style={{ gap: 6 }}>
            <View style={styles.row}><Text>Amount (XAF)</Text><Text style={{ fontWeight: '600' }}>{quote ? nf.format(quote.amountXaf) : '—'}</Text></View>
            <View style={styles.row}><Text>Receiver (NGN)</Text><Text style={{ fontSize: 16, fontWeight: '700' }}>{ngnFmt.format(receiveNgn)}</Text></View>
            <View style={styles.row}><Text>Fees</Text><Text>{quote ? nf.format(quote.feeTotalXaf) : 0} XAF</Text></View>
            <View style={styles.row}><Text>Total pay</Text><Text>{quote ? nf.format(quote.totalPayXaf) : '—'} XAF</Text></View>
            <View style={styles.row}><Text>Rate</Text><Text>{quote ? `1 XAF = NGN ${(receiveNgn/Math.max(1, quote.amountXaf)).toFixed(2)}` : '—'}</Text></View>
            <Text style={styles.ttlText}>Quote expires {quote ? new Date(quote.expiresAt).toLocaleTimeString() : '—'} ({ttlSec}s left)</Text>
          </View>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Payer phone (MSISDN)</Text>
          <TextInput value={msisdn} onChangeText={setMsisdn} placeholder="e.g. 2376XXXXXXXX" style={styles.input} />
          <TouchableOpacity style={[styles.payBtn, pending && styles.disabled]} disabled={pending || cooldownLeft>0} onPress={doConfirm}>
            <Text style={styles.payBtnText}>{pending ? 'Processing…' : 'Pay with Mobile Money'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24, gap: 12 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  headerTitle: { color: '#0B0F1A', fontSize: 16, fontWeight: '700' },
  linkText: { color: '#111827', textDecorationLine: 'underline' },
  errBox: { color: '#991B1B', borderColor: '#FECACA', borderWidth: 1, borderRadius: 8, padding: 8 },
  recipientCard: { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: '#F3F4F6', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 10 },
  recAvatar: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#E5E7EB', alignItems: 'center', justifyContent: 'center' },
  recAvatarText: { fontSize: 12, color: '#111827' },
  recName: { color: '#0B0F1A', fontWeight: '600' },
  recAcct: { color: '#1543A6', fontSize: 12 },
  recBank: { color: '#6B7280', fontSize: 12 },
  card: { backgroundColor: '#FFFFFF', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 12 },
  cardTitle: { color: '#111827', fontWeight: '700', marginBottom: 6 },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  ttlText: { color: '#6B7280', fontSize: 12 },
  input: { borderWidth: 1, borderColor: '#E5E7EB', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, color: '#0B0F1A' },
  payBtn: { marginTop: 12, height: 48, borderRadius: 999, backgroundColor: '#059669', alignItems: 'center', justifyContent: 'center' },
  payBtnText: { color: '#FFFFFF', fontWeight: '500' },
  disabled: { opacity: 0.6 },
});
