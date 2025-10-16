import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TouchableOpacity } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';

export default function TransferProcessingScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<any>();
  const { transferId } = route.params || {};

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 12 }]}>
      <View style={styles.container}>
        <Text style={styles.title}>Processing…</Text>
        <Text style={styles.sub}>Transfer ID: {transferId || '—'}</Text>
        <TouchableOpacity style={styles.primaryBtn} onPress={() => nav.navigate('TransferTimeline', { transferId: transferId || 1 })}>
          <Text style={styles.primaryBtnText}>View Timeline</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  title: { color: '#0B0F1A', fontSize: 18, fontWeight: '700', marginBottom: 6 },
  sub: { color: '#6B7280', marginBottom: 6 },
  primaryBtn: { marginTop: 16, backgroundColor: '#111827', borderRadius: 999, paddingVertical: 14, alignItems: 'center' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
});
