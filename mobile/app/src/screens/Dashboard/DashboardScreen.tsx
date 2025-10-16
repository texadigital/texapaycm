import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, ScrollView, RefreshControl, TouchableOpacity, Image } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';

export default function DashboardScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [dash, setDash] = React.useState<any>(null);
  const [feed, setFeed] = React.useState<any[]>([]);
  const [showTotal, setShowTotal] = React.useState<boolean>(() => true);
  const [period, setPeriod] = React.useState<'all'|'month'|'week'>('all');
  const [crossRate, setCrossRate] = React.useState<number | null>(null);
  const [fxUpdatedAt, setFxUpdatedAt] = React.useState<string | null>(null);

  const loadFx = React.useCallback(async () => {
    try {
      const r = await http.get('/api/mobile/pricing/rate-preview');
      const d = r?.data || {};
      const usd_to_xaf = Number(d.usd_to_xaf || d.usdToXaf || 0);
      const usd_to_ngn = Number(d.usd_to_ngn || d.usdToNgn || 0);
      if (usd_to_xaf && usd_to_ngn) {
        setCrossRate(usd_to_ngn / usd_to_xaf);
        setFxUpdatedAt(new Date().toLocaleTimeString());
      } else {
        setCrossRate(null);
        setFxUpdatedAt(null);
      }
    } catch {
      setCrossRate(null);
      setFxUpdatedAt(null);
    }
  }, []);

  const load = React.useCallback(async () => {
    try {
      setError(null);
      const res = await http.get('/api/mobile/dashboard');
      setDash(normalizeDashboard(res?.data ?? {}));
      try {
        const fr = await http.get('/api/mobile/transactions/feed', { params: { page: 1, perPage: 10 } });
        const months = Array.isArray(fr?.data?.months) ? fr.data.months : [];
        const all = months.flatMap((m: any) => Array.isArray(m.items) ? m.items : []);
        setFeed(all.filter((it: any) => it?.kind === 'transfer').slice(0, 10));
      } catch {
        setFeed([]);
      }
      loadFx();
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to load dashboard');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [loadFx]);

  React.useEffect(() => { load(); }, [load]);

  const onRefresh = () => { setRefreshing(true); load(); };

  const totalSentDisplay = React.useMemo(() => {
    const ts: any = dash?.totalSent;
    if (!ts) return currencySymbol('NGN') + '0.00';
    const minor = period === 'month' ? ts.monthMinor : period === 'week' ? ts.weekMinor : ts.allMinor;
    return formatMoneyFromMinor(minor || 0, ts.currency || 'NGN');
  }, [dash, period]);

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <ScrollView contentContainerStyle={styles.container} refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}>
        <View style={[styles.headerPill, { marginTop: 8 }]}>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <Image source={{ uri: dash?.user?.avatar_url || 'https://i.pravatar.cc/80' }} style={styles.avatar} />
            <View>
              <Text style={styles.headerHi}>Hi {dash?.firstName || dash?.user?.first_name || dash?.user?.name || ''}</Text>
              <View style={styles.pointsPill}><Text style={styles.pointsText}>{(dash?.user?.points ?? 22) + ' pts'}</Text></View>
            </View>
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'center' }}>
            <View style={styles.iconCircle}><Text style={styles.iconEmoji}>🔔</Text></View>
          </View>
        </View>

        <View style={styles.blueCard}>
          <View style={styles.blueTabs}>
            <Text style={styles.blueTabActive}>Total Sent</Text>
            <TouchableOpacity onPress={() => nav.navigate('TransfersHistory')}><Text style={styles.blueTab}>Transaction history</Text></TouchableOpacity>
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.blueValue}>{showTotal ? totalSentDisplay : '•••••'}</Text>
            <TouchableOpacity style={styles.whitePill} onPress={() => setShowTotal(v => !v)}>
              <Text style={styles.whitePillText}>{showTotal ? 'Hide' : 'Show'}</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.periodRow}>
            {(['all','month','week'] as const).map(p => (
              <TouchableOpacity key={p} style={[styles.segment, period===p && styles.segmentActive]} onPress={() => setPeriod(p)}>
                <Text style={[styles.segmentText, period===p && styles.segmentTextActive]}>{p==='all'?'All-time':p==='month'?'This month':'This week'}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitle}>Current Rate</Text>
            <TouchableOpacity onPress={loadFx}><Text style={styles.linkText}>Refresh</Text></TouchableOpacity>
          </View>
          {crossRate ? (
            <View style={{ flexDirection: 'row', alignItems: 'baseline', gap: 8 }}>
              <Text style={styles.rateValue}>1 XAF = NGN {crossRate.toFixed(2)}</Text>
              {fxUpdatedAt ? <Text style={styles.rateTime}>as of {fxUpdatedAt}</Text> : null}
            </View>
          ) : (
            <Text style={styles.rateTime}>Rate unavailable</Text>
          )}
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Send Money</Text>
          <Text style={styles.cardSub}>To bank · Pay with Mobile Money</Text>
          <TouchableOpacity style={[styles.primaryBtn, { marginTop: 10 }]} onPress={() => nav.navigate('TransferVerify')}>
            <Text style={styles.primaryBtnText}>Start Transfer</Text>
          </TouchableOpacity>
        </View>

        <Text style={[styles.sectionTitle, { marginTop: 16 }]}>Recent Transfers</Text>
        <View style={styles.listCard}>
          {loading ? (
            <Text style={styles.subtitle}>Loading…</Text>
          ) : error ? (
            <Text style={{ color: '#991B1B' }}>{error}</Text>
          ) : feed.length === 0 ? (
            <Text style={styles.subtitle}>No recent transfers.</Text>
          ) : (
            feed.map((it, i) => (
              <View key={it?.id || i} style={[styles.txRow, i>0 && styles.txRowDivider]}>
                <View style={styles.dotIcon}><Text style={{ color: '#6B7280' }}>{(it?.sign ?? 1) === -1 ? '↑' : '↓'}</Text></View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.txTitle} numberOfLines={1}>{it?.label || 'Transfer'}</Text>
                  <Text style={styles.txSub}>{it?.at ? new Date(it.at).toLocaleString() : ''}</Text>
                </View>
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.txAmount}>{(it?.sign ?? 1) === -1 ? '-' : '+'}{formatMoneyFromMinor(Math.abs(Number(it?.amountMinor || 0)), it?.currency || 'NGN')}</Text>
                  <Text style={[styles.txStatus, statusColor(it?.statusLabel || it?.status)]}>{(it?.statusLabel || it?.status || '').toString()}</Text>
                </View>
              </View>
            ))
          )}
        </View>

        {!dash?.kyc || (dash.kyc?.status ?? 'unverified') !== 'verified' ? (
          <View style={[styles.card, { backgroundColor: '#FEF3C7', borderColor: '#FDE68A' }]}>
            <Text style={[styles.cardSub, { color: '#92400E' }]}>Verify your identity to increase limits and keep transfers secure.</Text>
            <TouchableOpacity style={[styles.primaryBtn, { backgroundColor: '#111827', marginTop: 8 }]} onPress={() => nav.navigate('Profile')}>
              <Text style={styles.primaryBtnText}>Start KYC</Text>
            </TouchableOpacity>
          </View>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

function normalizeDashboard(raw: any) {
  const d = raw?.data && typeof raw.data === 'object' ? raw.data : raw;
  const user = d.user || d.profile || d.account?.owner || {};
  const totalSent = d.totalSent || null;
  return {
    firstName: d.firstName || user.first_name || '',
    user,
    totalSent,
  };
}

function currencySymbol(currency: string) {
  if (currency === 'NGN') return '₦';
  if (currency === 'XAF') return 'XAF ';
  return `${currency} `;
}

function formatMoneyFromMinor(minor: number, currency: string) {
  return `${currencySymbol(currency)}${(Number(minor || 0) / 100).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
}

function statusColor(s: any) {
  const t = String(s || '').toLowerCase();
  if (t.includes('success')) return { color: '#166534' };
  if (t.includes('pend')) return { color: '#92400E' };
  if (t.includes('fail') || t.includes('cancel')) return { color: '#991B1B' };
  return { color: '#6B7280' };
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flexGrow: 1, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  subtitle: { marginTop: 4, color: '#0B0F1A', fontWeight: '600' },
  headerPill: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#FFFFFF', padding: 12, borderRadius: 999, borderWidth: 1, borderColor: '#E8ECF8' },
  avatar: { width: 36, height: 36, borderRadius: 18, marginRight: 10 },
  headerHi: { color: '#0B0F1A', fontSize: 16, fontWeight: '700' },
  pointsPill: { backgroundColor: '#FFF7CC', borderRadius: 999, paddingHorizontal: 8, paddingVertical: 4, alignSelf: 'flex-start', marginTop: 4 },
  pointsText: { color: '#B45309', fontSize: 12, fontWeight: '600' },
  iconCircle: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E8ECF8', alignItems: 'center', justifyContent: 'center' },
  iconEmoji: { fontSize: 18 },

  blueCard: { backgroundColor: '#1543A6', borderRadius: 16, padding: 16, marginTop: 12 },
  blueTabs: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
  blueTab: { color: '#BFD2FF', fontWeight: '600' },
  blueTabActive: { color: '#FFFFFF', fontWeight: '700' },
  blueValue: { color: '#FFFFFF', fontSize: 28, fontWeight: '700' },
  whitePill: { alignSelf: 'flex-end', backgroundColor: '#FFFFFF', paddingVertical: 6, paddingHorizontal: 10, borderRadius: 999 },
  whitePillText: { color: '#1543A6', fontWeight: '700' },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  periodRow: { flexDirection: 'row', gap: 8, marginTop: 10 },
  segment: { borderWidth: 1, borderColor: '#BFD2FF', borderRadius: 6, paddingHorizontal: 10, paddingVertical: 6 },
  segmentActive: { backgroundColor: '#FFFFFF', borderColor: '#FFFFFF' },
  segmentText: { color: '#FFFFFF', fontWeight: '600' },
  segmentTextActive: { color: '#1543A6', fontWeight: '700' },

  card: { marginTop: 12, backgroundColor: '#FFFFFF', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 12 },
  cardHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  cardTitle: { color: '#111827', fontWeight: '700' },
  cardSub: { color: '#6B7280' },
  linkText: { color: '#111827', textDecorationLine: 'underline' },
  rateValue: { color: '#111827', fontSize: 20, fontWeight: '700' },
  rateTime: { color: '#6B7280' },

  sectionTitle: { color: '#111827', fontWeight: '700' },
  listCard: { marginTop: 8, backgroundColor: '#FFFFFF', borderRadius: 8, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 8 },
  txRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12 },
  txRowDivider: { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: '#E8ECF8' },
  dotIcon: { width: 18, height: 18, borderRadius: 9, borderWidth: 1, borderColor: '#D1D5DB', alignItems: 'center', justifyContent: 'center', marginRight: 10 },
  txTitle: { color: '#0B0F1A', fontWeight: '600' },
  txSub: { color: '#6B7280', fontSize: 12, marginTop: 2 },
  txAmount: { fontWeight: '700' },
  txStatus: { fontSize: 12, marginTop: 2 },

  primaryBtn: { backgroundColor: '#111827', borderRadius: 6, paddingVertical: 12, alignItems: 'center' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
});
