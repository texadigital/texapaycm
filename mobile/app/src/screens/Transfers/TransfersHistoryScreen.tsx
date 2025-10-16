import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, FlatList, RefreshControl, TouchableOpacity } from 'react-native';
import http from '../../lib/http';

export default function TransfersHistoryScreen() {
  const [items, setItems] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [refreshing, setRefreshing] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  const load = React.useCallback(async () => {
    try {
      setError(null);
      // Try canonical endpoint, fallback to dashboard recent transfers
      const res = await http.get('/api/mobile/transfers');
      const list = Array.isArray(res?.data?.data) ? res.data.data : (Array.isArray(res?.data) ? res.data : []);
      setItems(list);
    } catch (e: any) {
      try {
        const dash = await http.get('/api/mobile/dashboard');
        const list = Array.isArray(dash?.data?.recent_transfers) ? dash.data.recent_transfers : [];
        setItems(list);
      } catch (err: any) {
        setError(err?.response?.data?.message || err?.message || 'Failed to load transfers');
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  React.useEffect(() => { load(); }, [load]);

  const onRefresh = () => { setRefreshing(true); load(); };

  const renderItem = ({ item }: { item: any }) => (
    <View style={styles.row}>
      <View style={styles.dot}><Text style={{ color: '#6B7280' }}>i</Text></View>
      <View style={{ flex: 1 }}>
        <Text style={styles.title} numberOfLines={1}>Transfer to {item?.to_name || item?.title || 'Recipient'}</Text>
        <Text style={styles.sub}>{item?.date || item?.created_at || ''}</Text>
      </View>
      <View style={{ alignItems: 'flex-end' }}>
        <Text style={styles.amount}>{(item?.sign || '+')}₦{Number(Math.abs(item?.amount ?? 0)).toLocaleString()}</Text>
        <Text style={[styles.status, statusColor(item?.status || item?.state)]}>{(item?.status || item?.state || 'Successful').toString()}</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }] }>
      <FlatList
        data={items}
        keyExtractor={(it, i) => String(it?.id || i)}
        renderItem={renderItem}
        contentContainerStyle={styles.container}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListHeaderComponent={(
          <>
            <Text style={styles.header}>Transfer History</Text>
            {loading ? <Text style={styles.sub}>Loading…</Text> : null}
            {error ? <Text style={[styles.sub, { color: '#991B1B' }]}>{error}</Text> : null}
          </>
        )}
        ListEmptyComponent={!loading ? <Text style={styles.sub}>No transfers found</Text> : null}
      />
    </SafeAreaView>
  );
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
  container: { paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  header: { color: '#0B0F1A', fontSize: 20, fontWeight: '700', marginBottom: 8 },
  sub: { color: '#6B7280', marginBottom: 8 },
  row: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: '#E8ECF8' },
  dot: { width: 18, height: 18, borderRadius: 9, borderWidth: 1, borderColor: '#D1D5DB', alignItems: 'center', justifyContent: 'center', marginRight: 10 },
  title: { color: '#0B0F1A', fontWeight: '600' },
  amount: { color: '#0B0F1A', fontWeight: '700' },
  status: { fontSize: 12, marginTop: 2 },
});
