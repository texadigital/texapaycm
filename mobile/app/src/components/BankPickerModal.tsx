import React from 'react';
import { Modal, View, Text, StyleSheet, TouchableOpacity, TextInput, FlatList } from 'react-native';

export type BankItem = { bankCode: string; name: string; icon?: string };

export default function BankPickerModal({
  open,
  onClose,
  onSelect,
  matched = [],
  frequent = [],
  all = [],
}: {
  open: boolean;
  onClose: () => void;
  onSelect: (b: BankItem) => void;
  matched?: BankItem[];
  frequent?: BankItem[];
  all?: BankItem[];
}) {
  const [query, setQuery] = React.useState('');

  const filteredAll = React.useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return all;
    return all.filter(b => (b.name||'').toLowerCase().includes(q));
  }, [all, query]);

  return (
    <Modal visible={open} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.sheet}>
          <View style={styles.headerRow}>
            <Text style={styles.title}>Select Bank</Text>
            <TouchableOpacity onPress={onClose}><Text style={styles.close}>✕</Text></TouchableOpacity>
          </View>
          <View style={styles.searchWrap}>
            <TextInput
              placeholder="Search Bank Name"
              value={query}
              onChangeText={setQuery}
              style={styles.search}
            />
          </View>
          <FlatList
            data={filteredAll}
            keyExtractor={(it, idx) => `${it.bankCode}-${idx}`}
            ListHeaderComponent={(
              <View>
                {matched.length > 0 && (
                  <View style={{ paddingHorizontal: 16 }}>
                    <Text style={styles.sectionLabel}>Matched Bank</Text>
                    <View style={styles.grid}>
                      {matched.slice(0,6).map((b, i) => (
                        <TouchableOpacity key={`${b.bankCode}-${i}`} style={styles.gridItem} onPress={() => onSelect(b)}>
                          <View style={styles.gridIcon} />
                          <Text style={styles.gridText} numberOfLines={1}>{b.name}</Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                  </View>
                )}
                {frequent.length > 0 && (
                  <View style={{ paddingHorizontal: 16, marginTop: 12 }}>
                    <Text style={styles.sectionLabel}>Frequently Used Bank</Text>
                    <View style={styles.grid}>
                      {frequent.slice(0,6).map((b, i) => (
                        <TouchableOpacity key={`${b.bankCode}-${i}`} style={styles.gridItem} onPress={() => onSelect(b)}>
                          <View style={styles.gridIcon} />
                          <Text style={styles.gridText} numberOfLines={1}>{b.name}</Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                  </View>
                )}
                <View style={{ paddingHorizontal: 16, marginTop: 12 }}>
                  <View style={styles.alphaRow}>
                    {'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').map(ch => (
                      <View key={ch} style={styles.alphaChip}><Text style={styles.alphaText}>{ch}</Text></View>
                    ))}
                  </View>
                </View>
                <View style={{ height: 8 }} />
              </View>
            )}
            renderItem={({ item }) => (
              <TouchableOpacity style={[styles.listRow, { paddingHorizontal: 16 }]} onPress={() => onSelect(item)}>
                <View style={styles.listIcon} />
                <Text style={styles.listText}>{item.name}</Text>
              </TouchableOpacity>
            )}
          />
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.3)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: '#FFFFFF', borderTopLeftRadius: 16, borderTopRightRadius: 16, maxHeight: '88%' },
  headerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 12 },
  title: { color: '#111827', fontWeight: '700' },
  close: { color: '#6B7280', fontSize: 18 },
  searchWrap: { paddingHorizontal: 16 },
  search: { backgroundColor: '#F3F4F6', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 10, color: '#0B0F1A' },
  sectionLabel: { color: '#6B7280', fontSize: 12, marginTop: 12, marginBottom: 8 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  gridItem: { width: '30%', alignItems: 'center', paddingVertical: 10, borderRadius: 12, backgroundColor: '#F9FAFB' },
  gridIcon: { width: 28, height: 28, borderRadius: 14, backgroundColor: '#E5E7EB', marginBottom: 6 },
  gridText: { color: '#111827', fontSize: 12 },
  alphaRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  alphaChip: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#E5E7EB', alignItems: 'center', justifyContent: 'center' },
  alphaText: { color: '#111827', fontSize: 12 },
  listRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: '#E8ECF8' },
  listIcon: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#E5E7EB', marginRight: 12 },
  listText: { color: '#0B0F1A' },
});
