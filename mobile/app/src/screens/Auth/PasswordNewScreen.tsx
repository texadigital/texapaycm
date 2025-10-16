import React, { useState } from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';

type Props = NativeStackScreenProps<RootStackParamList, 'PasswordNew'>;

export default function PasswordNewScreen({ route, navigation }: Props) {
  const { phone, code } = route.params;
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const onSubmit = async () => {
    if (!password || password !== confirm) {
      setError('Passwords do not match');
      return;
    }
    try {
      setSubmitting(true);
      setError(null);
      await http.post('/api/mobile/auth/password/reset', { phone, code, password });
      navigation.replace('PasswordDone', { phone, password });
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to reset password');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }]}>
      <View style={styles.container}>
        <Text style={styles.title}>Set new password</Text>
        {error ? <Text style={[styles.msg, { color: '#991B1B' }]}>{error}</Text> : null}
        <Text style={styles.label}>New password</Text>
        <TextInput value={password} onChangeText={setPassword} placeholder="New password" placeholderTextColor="#9CA3AF" style={styles.input} secureTextEntry />
        <Text style={styles.label}>Confirm password</Text>
        <TextInput value={confirm} onChangeText={setConfirm} placeholder="Confirm new password" placeholderTextColor="#9CA3AF" style={styles.input} secureTextEntry />
        <TouchableOpacity style={[styles.primaryBtn, (submitting || !password || !confirm) && styles.primaryBtnDisabled]} onPress={onSubmit} disabled={submitting || !password || !confirm}>
          <Text style={styles.primaryBtnText}>{submitting ? 'Please wait…' : 'Save password'}</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 24, paddingTop: 16 },
  title: { fontSize: 22, fontWeight: '700', color: '#0B0F1A', marginBottom: 16 },
  label: { marginTop: 8, marginBottom: 6, color: '#0B0F1A', fontSize: 14, fontWeight: '600' },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A', height: 52 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center', marginTop: 16 },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  msg: { marginBottom: 8 },
});
