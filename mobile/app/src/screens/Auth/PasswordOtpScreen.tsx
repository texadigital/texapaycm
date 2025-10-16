import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';

type Props = NativeStackScreenProps<RootStackParamList, 'PasswordOtp'>;

export default function PasswordOtpScreen({ route, navigation }: Props) {
  const phone = route.params?.phone as string;
  const [code, setCode] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);
  const [ok, setOk] = React.useState<string | null>(null);
  const [resending, setResending] = React.useState(false);
  const [countdown, setCountdown] = React.useState(60);

  React.useEffect(() => {
    setCountdown(60);
    const id = setInterval(() => setCountdown((c) => (c > 0 ? c - 1 : 0)), 1000);
    return () => clearInterval(id);
  }, [phone]);

  const resend = async () => {
    if (countdown > 0) return;
    try {
      setResending(true);
      setError(null); setOk(null);
      await http.post('/api/mobile/auth/password/forgot', { identifier: phone, phone });
      setOk('OTP resent');
      setCountdown(60);
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to resend OTP');
    } finally {
      setResending(false);
    }
  };

  const next = () => {
    if (!code) { setError('Enter the OTP code'); return; }
    navigation.navigate('PasswordNew', { phone, code });
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }] }>
      <View style={styles.container}>
        <Text style={styles.title}>Enter OTP</Text>
        {error ? <Text style={[styles.msg, { color: '#991B1B' }]}>{error}</Text> : null}
        {ok ? <Text style={[styles.msg, { color: '#166534' }]}>{ok}</Text> : null}
        <Text style={styles.label}>Code sent to {phone}</Text>
        <TextInput value={code} onChangeText={(t) => setCode(t.replace(/\D+/g, '').slice(0, 6))} keyboardType="number-pad" placeholder="Enter OTP" placeholderTextColor="#9CA3AF" style={styles.input} />
        <TouchableOpacity style={[styles.primaryBtn, !code && styles.primaryBtnDisabled]} onPress={next} disabled={!code}>
          <Text style={styles.primaryBtnText}>Continue</Text>
        </TouchableOpacity>
        <View style={{ height: 12 }} />
        <TouchableOpacity onPress={resend} disabled={countdown > 0 || resending}>
          <Text style={styles.link}>{resending ? 'Resending…' : countdown > 0 ? `Resend in ${countdown}s` : 'Resend code'}</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 24, paddingTop: 16 },
  title: { fontSize: 22, fontWeight: '700', color: '#0B0F1A', marginBottom: 16 },
  label: { marginBottom: 6, color: '#0B0F1A', fontSize: 14, fontWeight: '600' },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A', height: 52 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center', marginTop: 16 },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  link: { color: '#1543A6', fontWeight: '500', textAlign: 'center' },
  msg: { marginBottom: 8 },
});
