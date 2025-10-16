import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, FlatList } from 'react-native';
import { useRoute } from '@react-navigation/native';

export default function TransferTimelineScreen() {
  const route = useRoute<any>();
  const { transferId } = route.params || {};
  const steps = [
    { id: '1', label: 'Created' },
    { id: '2', label: 'Processing' },
    { id: '3', label: 'Completed' },
  ];

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <View style={styles.container}>
        <Text style={styles.title}>Transfer #{transferId || '—'}</Text>
        <FlatList
          data={steps}
          keyExtractor={(it) => it.id}
          renderItem={({ item }) => (
            <View style={styles.row}><Text style={styles.stepText}>{item.label}</Text></View>
          )}
        />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  title: { color: '#0B0F1A', fontSize: 18, fontWeight: '700', marginBottom: 6 },
  row: { paddingVertical: 10, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: '#E8ECF8' },
  stepText: { color: '#0B0F1A' },
});
