import React, { useMemo, useState } from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity, KeyboardAvoidingView, useWindowDimensions } from 'react-native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useNavigation } from '@react-navigation/native';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';

const BASE_WIDTH = 375;

export default function PasswordForgotScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const { width, height } = useWindowDimensions();
  const scale = Math.max(0.85, Math.min(1.25, width / BASE_WIDTH));
  const metrics = useMemo(() => {
    const pagePaddingH = Math.round(Math.max(24, Math.min(44, width * 0.10)));
    const ctaPaddingH = Math.round(Math.max(16, Math.min(28, width * 0.06)));
    const ctaBottom = Math.round(Math.max(56, Math.min(96, height * 0.08)));
    const fieldHeight = Math.round(Math.max(48, Math.min(56, 52 * scale)));
    return { pagePaddingH, ctaPaddingH, ctaBottom, fieldHeight };
  }, [width, height, scale]);

  const [phone, setPhone] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [topError, setTopError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [cooldown, setCooldown] = useState(0);
  React.useEffect(() => {
    if (cooldown <= 0) return;
    const id = setInterval(() => setCooldown((c) => (c > 0 ? c - 1 : 0)), 1000);
    return () => clearInterval(id);
  }, [cooldown]);

  function norm(p: string) {
    const d = p.replace(/\D+/g, '');
    if (d.startsWith('237')) return '+'+d;
    if (d.length===9 && d.startsWith('6')) return '+237'+d;
    return d ? '+'+d : '';
  }
  const validPhone = () => {
    const d = phone.replace(/\D+/g, '');
    return d.length >= 9; // minimal guard
  };

  const onSubmit = async () => {
    if (!phone || !validPhone()) { setTopError('Enter a valid phone number'); return; }
    try {
      setSubmitting(true);
      setTopError(null);
      setOk(null);
      const p = norm(phone);
      await http.post('/api/mobile/auth/password/forgot', { identifier: p, phone: p });
      setOk('OTP sent. Check your messages.');
      nav.navigate('PasswordOtp', { phone: p });
    } catch (e: any) {
      const msg = e?.response?.data?.message || e.message || 'Failed to send OTP';
      setTopError(msg);
      // If rate limited, start a local cooldown (fallback 60s)
      if (typeof msg === 'string' && /too many/i.test(msg)) setCooldown(60);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 2 : 0 }]}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <View style={[styles.container, { paddingHorizontal: metrics.pagePaddingH }]}>
          <Text style={styles.title}>Forgot password</Text>
          {topError ? <Text style={[styles.msg, { color: '#991B1B' }]}>{topError}</Text> : null}
          {ok ? <Text style={[styles.msg, { color: '#166534' }]}>{ok}</Text> : null}

          <Text style={styles.label}>Phone</Text>
          <TextInput value={phone} onChangeText={setPhone} placeholder="Enter your phone number" placeholderTextColor="#9CA3AF" style={[styles.input, { height: metrics.fieldHeight }]} keyboardType="phone-pad" />
        </View>
      </KeyboardAvoidingView>
      <View style={[styles.ctaWrap, { marginBottom: metrics.ctaBottom, paddingHorizontal: metrics.ctaPaddingH }]}>
        <TouchableOpacity activeOpacity={0.9} style={[styles.primaryBtn, ((submitting || !validPhone() || cooldown>0)) && styles.primaryBtnDisabled]} onPress={onSubmit} disabled={submitting || !validPhone() || cooldown>0}>
          <Text style={styles.primaryBtnText}>{submitting ? 'Please wait…' : (cooldown>0 ? `Retry in ${cooldown}s` : 'Send OTP')}</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingTop: 20 },
  title: { fontSize: 22, fontWeight: '700', color: '#0B0F1A', marginBottom: 16 },
  label: { marginBottom: 6, color: '#0B0F1A', fontSize: 14, fontWeight: '600' },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A' },
  ctaWrap: {},
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center' },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  msg: { marginBottom: 8 },
})
