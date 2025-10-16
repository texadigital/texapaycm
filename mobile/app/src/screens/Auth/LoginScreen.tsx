import React, { useMemo, useState } from 'react';
import { View, Text, SafeAreaView, StyleSheet, TextInput, TouchableOpacity, Platform, StatusBar, useWindowDimensions, KeyboardAvoidingView } from 'react-native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useNavigation } from '@react-navigation/native';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { setAccessToken } from '../../lib/auth';

const BASE_WIDTH = 375;

export default function LoginScreen() {
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
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [topError, setTopError] = useState<string | null>(null);

  function norm(p: string) {
    const d = p.replace(/\D+/g, '');
    if (d.startsWith('237')) return '+'+d;
    if (d.length===9 && d.startsWith('6')) return '+237'+d;
    return d ? '+'+d : '';
  }

  const onSubmit = async () => {
    if (!phone || !password) return;
    try {
      setSubmitting(true);
      setTopError(null);
      const res = await http.post('/api/mobile/auth/login', { phone: norm(phone), password });
      // Robust extraction: headers, known fields, and deep-search for JWT-like tokens
      const headerAuth: string | undefined = (res as any)?.headers?.authorization || (res as any)?.headers?.Authorization;
      const headerToken = headerAuth && headerAuth.startsWith('Bearer ') ? headerAuth.slice(7) : null;
      const body: any = res?.data ?? {};

      function looksLikeJwt(s: any): s is string {
        return typeof s === 'string' && s.split('.').length === 3 && s.length > 20;
      }
      function deepFindJwt(obj: any): string | null {
        try {
          const stack: any[] = [obj];
          const seen = new Set<any>();
          while (stack.length) {
            const cur = stack.pop();
            if (!cur || typeof cur !== 'object' || seen.has(cur)) continue;
            seen.add(cur);
            for (const k of Object.keys(cur)) {
              const v = (cur as any)[k];
              if (looksLikeJwt(v)) return v as string;
              if (v && typeof v === 'object') stack.push(v);
            }
          }
        } catch {}
        return null;
      }

      if (__DEV__) {
        const keys = body && typeof body === 'object' ? Object.keys(body) : [];
        // Avoid logging token contents; just indicate presence
        // eslint-disable-next-line no-console
        console.log('[AuthDebug] login response keys:', keys);
        // eslint-disable-next-line no-console
        console.log('[AuthDebug] header Authorization present:', !!headerAuth);
      }

      const token = headerToken
        || body.access_token
        || body.token
        || body.jwt
        || body?.data?.token
        || body?.data?.access_token
        || body?.accessToken
        || body?.token?.access
        || body?.token?.access_token
        || deepFindJwt(body)
        || null;
      if (!token) {
        setTopError('Login succeeded but no bearer token returned');
        return;
      }
      await setAccessToken(token);
      try {
        const st = await http.get('/api/mobile/policies/status');
        if (st?.data?.accepted) {
          nav.replace('Dashboard');
        } else {
          nav.replace('Privacy');
        }
      } catch {
        // If token is present but status check fails, show Privacy to allow manual accept
        nav.replace('Privacy');
      }
    } catch (e: any) {
      setTopError(e?.response?.data?.message || e.message || 'Login failed');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 2 : 0 }]}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <View style={[styles.container, { paddingHorizontal: metrics.pagePaddingH }]}>
          {topError ? (
            <View style={styles.errorBar}><Text style={styles.errorText}>{topError}</Text></View>
          ) : null}
          <Text style={styles.title}>Welcome back</Text>

          <Text style={styles.label}>Phone</Text>
          <TextInput
            value={phone}
            onChangeText={setPhone}
            placeholder="Enter your phone number"
            placeholderTextColor="#9CA3AF"
            style={[styles.inputSoft, { height: metrics.fieldHeight }]}
            keyboardType="phone-pad"
            returnKeyType="next"
          />
          <View style={{ height: 12 }} />
          <Text style={styles.label}>Password</Text>
          <TextInput
            value={password}
            onChangeText={setPassword}
            placeholder="Your password"
            placeholderTextColor="#9CA3AF"
            style={[styles.inputSoft, { height: metrics.fieldHeight }]}
            secureTextEntry
            returnKeyType="done"
          />
        </View>
      </KeyboardAvoidingView>

      <View style={[styles.ctaWrap, { marginBottom: metrics.ctaBottom, paddingHorizontal: metrics.ctaPaddingH }]}>
        <TouchableOpacity activeOpacity={0.9} style={[styles.primaryBtn, (!phone || !password || submitting) && styles.primaryBtnDisabled]} onPress={onSubmit} disabled={!phone || !password || submitting}>
          <Text style={styles.primaryBtnText}>{submitting ? 'Please wait…' : 'Login'}</Text>
        </TouchableOpacity>
        <TouchableOpacity activeOpacity={0.8} style={{ alignItems: 'center', marginTop: 12 }} onPress={() => nav.navigate('PasswordForgot')}>
          <Text style={styles.linkText}>Forgot Password ?</Text>
        </TouchableOpacity>
        <TouchableOpacity activeOpacity={0.8} style={{ alignItems: 'center', marginTop: 12 }} onPress={() => nav.navigate('Register')}>
          <Text style={styles.linkText}>Create a new account</Text>
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
  inputSoft: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A' },
  ctaWrap: {},
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center' },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  linkText: { color: '#1543A6', fontWeight: '500' },
  errorBar: { backgroundColor: '#FEE2E2', borderColor: '#FCA5A5', borderWidth: 1, borderRadius: 10, paddingVertical: 10, paddingHorizontal: 12, marginBottom: 12 },
  errorText: { color: '#991B1B' },
});
