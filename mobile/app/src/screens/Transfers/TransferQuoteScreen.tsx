import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { getQuoteState, setQuoteState, getRatePreview, setRatePreview, setSelectedQuote } from '../../lib/transferState';

type QuoteRes = {
  success: boolean;
  quote: {
    id: number;
    ref: string;
    amountXaf: number;
    feeTotalXaf: number;
    totalPayXaf: number;
    receiveNgnMinor: number;
    adjustedRate: number;
    expiresAt: string;
    ttlSeconds?: number;
    components?: {
      fxMarginBps?: number | null;
      percentBps?: number | null;
      fixedFeeXaf?: number | null;
      percentFeeXaf?: number | null;
      levyXaf?: number | null;
      totalFeeXaf?: number | null;
    } | null;
  };
};

export default function TransferQuoteScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<any>();
  const recipient = route.params?.recipient || {};
  const bankCode: string = recipient.bankCode || '';
  const bankName: string = recipient.bankName || '';
  const account: string = recipient.account || '';
  const accountName: string = recipient.accountName || '';

  const nf = React.useMemo(() => new Intl.NumberFormat('en-NG'), []);
  const ngnFmt = React.useMemo(() => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 }), []);

  const initial = getQuoteState();
  const [amount, setAmount] = React.useState<number>(typeof initial?.amount === 'number' ? initial!.amount! : 0);
  const [amountText, setAmountText] = React.useState<string>(initial?.amount ? nf.format(initial.amount) : '');
  const [quoteRes, setQuoteRes] = React.useState<QuoteRes | null>(null);
  const [err, setErr] = React.useState<string | null>(null);
  const [ttlSec, setTtlSec] = React.useState(0);
  const [autoRefreshing, setAutoRefreshing] = React.useState(false);
  const [previewRate, setPreviewRateState] = React.useState<number | null>(() => getRatePreview()?.rate || null);
  const controllerRef = React.useRef<AbortController | null>(null);
  const lastKeyRef = React.useRef<string>('');
  const [rateLimitedUntil, setRateLimitedUntil] = React.useState<number>(0);
  const debounceRef = React.useRef<any>(null);

  React.useEffect(() => {
    setQuoteState({ amount, bankCode, account });
  }, [amount, bankCode, account]);

  function formatLimitError(e: any): string {
    const d = e?.response?.data || {};
    const code = d.code || d.error || '';
    const msg = d.message || e?.message;
    const min = d.minXaf ?? d.min; const max = d.maxXaf ?? d.max;
    const rd = d.remainingXafDay ?? d.remainingDay; const rm = d.remainingXafMonth ?? d.remainingMonth;
    const parts: string[] = [];
    if (code) parts.push(`[${code}]`);
    if (msg) parts.push(String(msg));
    if (min != null) parts.push(`Minimum: ${min} XAF`);
    if (max != null) parts.push(`Maximum: ${max} XAF`);
    if (rd != null) parts.push(`Remaining today: ${rd} XAF`);
    if (rm != null) parts.push(`Remaining this month: ${rm} XAF`);
    return parts.join(' · ');
  }

  async function doQuote(a: number) {
    setErr(null);
    try { controllerRef.current?.abort(); } catch {}
    controllerRef.current = new AbortController();
    const res = await http.post('/api/mobile/transfers/quote', { amountXaf: Number(a), bankCode, accountNumber: account }, { signal: (controllerRef.current as any).signal });
    return res.data as QuoteRes;
  }

  React.useEffect(() => {
    if (!amount || !bankCode || !account) return;
    if (Date.now() < rateLimitedUntil) return;
    const key = `${bankCode}:${account}:${amount}`;
    if (lastKeyRef.current === key && quoteRes?.quote) return;
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(async () => {
      lastKeyRef.current = key;
      try {
        const d = await doQuote(amount);
        setQuoteRes(d);
        const implied = (d.quote.receiveNgnMinor / 100) / Math.max(1, d.quote.amountXaf);
        setRatePreview(implied);
        if (!previewRate) setPreviewRateState(implied);
      } catch (e: any) {
        if (e?.response?.status === 429) {
          const raHdr = e?.response?.headers?.['retry-after'];
          const ra = Math.max(5, Math.min(120, Number(raHdr) || Number(e?.response?.data?.retryAfterSeconds) || 12));
          setRateLimitedUntil(Date.now() + ra * 1000);
          setErr(`Please wait ${ra}s before trying again.`);
        } else {
          setErr(formatLimitError(e));
        }
      }
    }, 450);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [amount, bankCode, account, rateLimitedUntil]);

  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await http.get('/api/mobile/pricing/rate-preview');
        const data = res.data || {};
        const usdToXaf = Number(data.usd_to_xaf || data.usdToXaf || 0);
        const usdToNgn = Number(data.usd_to_ngn || data.usdToNgn || 0);
        if (!cancelled && usdToXaf > 0 && usdToNgn > 0) {
          const cross = usdToNgn / usdToXaf; // NGN per XAF
          setPreviewRateState(cross);
          setRatePreview(cross);
        }
      } catch {}
    })();
    return () => { cancelled = true; };
  }, []);

  React.useEffect(() => {
    if (!quoteRes?.quote?.expiresAt) { setTtlSec(0); return; }
    const expires = new Date(quoteRes.quote.expiresAt).getTime();
    const tick = () => setTtlSec(Math.max(0, Math.floor((expires - Date.now()) / 1000)));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quoteRes?.quote?.expiresAt]);

  React.useEffect(() => {
    if (!quoteRes?.quote || ttlSec <= 0) return;
    if (ttlSec <= 5 && Date.now() >= rateLimitedUntil) {
      setAutoRefreshing(true);
      doQuote(amount).then((d) => setQuoteRes(d)).finally(() => setAutoRefreshing(false));
    }
  }, [ttlSec, quoteRes?.quote, amount, rateLimitedUntil]);

  function proceed() {
    const qr = quoteRes;
    if (!qr?.quote || ttlSec <= 0) { setErr('Get fresh quote'); return; }
    setSelectedQuote({ quote: qr.quote, recipient: { bankCode, bankName, account, accountName } });
    nav.navigate('TransferConfirm', { quoteId: String(qr.quote.id) });
  }

  const receiveNgn = quoteRes?.quote?.receiveNgnMinor ? (quoteRes.quote.receiveNgnMinor / 100).toFixed(2) : '0.00';
  const effPreview = (!quoteRes?.quote && previewRate && amount) ? (amount * previewRate).toFixed(2) : null;
  const impliedRate = quoteRes?.quote ? ((quoteRes.quote.receiveNgnMinor / 100) / Math.max(1, quoteRes.quote.amountXaf)).toFixed(2) : null;

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <View style={styles.container}>
        <View style={styles.headerRow}>
          <Text style={styles.headerTitle}>Transfer To Bank Account</Text>
          <TouchableOpacity onPress={() => nav.navigate('TransfersHistory')}><Text style={styles.linkText}>History</Text></TouchableOpacity>
        </View>

        {!!err && <Text style={styles.errBox}>{err}</Text>}

        <View style={styles.recipientCard}>
          <View style={styles.recAvatar}><Text style={styles.recAvatarText}>{(bankName || '•').slice(0,1).toUpperCase()}</Text></View>
          <View style={{ flex: 1, minWidth: 0 }}>
            <Text style={styles.recName} numberOfLines={1}>{accountName || 'Recipient'}</Text>
            <Text style={styles.recAcct} numberOfLines={1}>{account}</Text>
            <Text style={styles.recBank} numberOfLines={1}>{bankName}</Text>
          </View>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Amount</Text>
          <TextInput
            value={amountText}
            onChangeText={(raw) => {
              const digits = raw.replace(/[^0-9]/g, '');
              const num = digits ? Number(digits) : 0;
              setAmount(num);
              setAmountText(digits ? nf.format(num) : '');
            }}
            keyboardType="number-pad"
            placeholder="XAF 100–5,000,000"
            style={styles.input}
          />
          {quoteRes ? (
            <View style={styles.rateRow}><View style={styles.rateDot} /><Text style={styles.rateText}>Rate: 1 XAF = NGN {impliedRate}</Text></View>
          ) : previewRate ? (
            <View style={styles.rateRow}><View style={styles.rateDot} /><Text style={styles.rateText}>Rate: 1 XAF = NGN {previewRate.toFixed(2)}</Text></View>
          ) : null}
        </View>

        <View style={{ gap: 8 }}>
          <View>
            <Text style={{ color: '#0B0F1A' }}>Receiver gets</Text>
            <Text style={styles.receiveValue}>
              {quoteRes ? ngnFmt.format(Number(receiveNgn)) : (effPreview ? ngnFmt.format(Number(effPreview)) : ngnFmt.format(0))}
            </Text>
            {quoteRes?.quote ? (
              <Text style={styles.ttlText}>Quote expires {new Date(quoteRes.quote.expiresAt).toLocaleTimeString()} ({ttlSec}s left){autoRefreshing ? ' – refreshing…' : ''}</Text>
            ) : null}
          </View>

          {quoteRes?.quote ? (
            <View style={styles.breakdownBox}>
              <View style={styles.row}><Text>Send amount</Text><Text>XAF {nf.format(quoteRes.quote.amountXaf)}</Text></View>
              <View style={styles.row}><Text>Fees</Text><Text>XAF {nf.format(quoteRes.quote.feeTotalXaf)}</Text></View>
              <View style={styles.row}><Text>Total to pay</Text><Text style={{ fontWeight: '600' }}>XAF {nf.format(quoteRes.quote.totalPayXaf)}</Text></View>
              <View style={styles.row}><Text>Effective rate</Text><Text>1 XAF = NGN {impliedRate}</Text></View>
            </View>
          ) : null}

          <TouchableOpacity style={[styles.ctaBtn, (!quoteRes?.quote || ttlSec <= 0) && styles.disabled]} disabled={!quoteRes?.quote || ttlSec <= 0} onPress={proceed}>
            <Text style={styles.ctaText}>{ttlSec <= 0 && amount ? 'Get fresh quote' : 'Next'}</Text>
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
  card: { backgroundColor: '#FFFFFF', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 12, gap: 8 },
  cardTitle: { color: '#111827', fontWeight: '700' },
  input: { borderBottomWidth: 1, borderBottomColor: '#E5E7EB', paddingVertical: 10, color: '#0B0F1A' },
  rateRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  rateDot: { width: 12, height: 12, borderRadius: 6, backgroundColor: '#111827' },
  rateText: { color: '#111827', fontSize: 12 },
  receiveValue: { color: '#0B0F1A', fontSize: 22, fontWeight: '700' },
  ttlText: { color: '#6B7280', fontSize: 12 },
  breakdownBox: { borderWidth: 1, borderColor: '#E5E7EB', borderRadius: 8, padding: 10, gap: 6 },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  ctaBtn: { height: 48, borderRadius: 999, backgroundColor: '#059669', alignItems: 'center', justifyContent: 'center' },
  ctaText: { color: '#FFFFFF', fontWeight: '500' },
  disabled: { opacity: 0.5 },
});
