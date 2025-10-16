import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TouchableOpacity } from 'react-native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useNavigation } from '@react-navigation/native';
import type { RootStackParamList } from '../../navigation/AppNavigator';

export default function TransfersStartScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }] }>
      <View style={styles.container}>
        <Text style={styles.title}>Start Transfer</Text>
        <Text style={styles.sub}>Choose how you want to send money</Text>

        <TouchableOpacity style={styles.primaryBtn} onPress={() => nav.navigate('TransferVerify')}>
          <Text style={styles.primaryBtnText}>Transfer to Bank Account</Text>
        </TouchableOpacity>

        <TouchableOpacity style={[styles.outlineBtn, { marginTop: 12 }]} onPress={() => nav.navigate('TransfersHistory')}>
          <Text style={styles.outlineBtnText}>Transfer History</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 16, paddingTop: 16 },
  title: { color: '#0B0F1A', fontSize: 20, fontWeight: '700', marginBottom: 8 },
  sub: { color: '#6B7280', marginBottom: 16 },
  primaryBtn: { backgroundColor: '#111827', borderRadius: 6, paddingVertical: 12, alignItems: 'center', marginTop: 8 },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '700' },
  outlineBtn: { borderWidth: 1, borderColor: '#D1D5DB', borderRadius: 6, paddingVertical: 12, alignItems: 'center' },
  outlineBtnText: { color: '#111827', fontWeight: '700' },
})
