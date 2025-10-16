import React, { useEffect } from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TouchableOpacity } from 'react-native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useNavigation, useRoute } from '@react-navigation/native';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { setAccessToken } from '../../lib/auth';

export default function PasswordDoneScreen() {
  const nav = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const route = useRoute<any>();
  const tmpPhone = route.params?._tmp_phone as string | undefined;
  const tmpPassword = route.params?._tmp_password as string | undefined;
  const [error, setError] = React.useState<string | null>(null);

  useEffect(() => {
    (async () => {
      if (tmpPhone && tmpPassword) {
        try {
          const res = await http.post('/api/mobile/auth/login', { phone: tmpPhone, password: tmpPassword });
          const body: any = res?.data ?? {};
          const token = body.access_token || body.token || body.jwt || body?.data?.token || body?.data?.access_token || body?.accessToken || body?.token?.access || body?.token?.access_token || null;
          if (token) {
            await setAccessToken(token);
            nav.reset({ index: 0, routes: [{ name: 'Dashboard' }] });
            return;
          }
        } catch (e: any) {
          setError(e?.response?.data?.message || e.message || 'Auto-login failed');
        }
      }
    })();
  }, [tmpPhone, tmpPassword, nav]);

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }]}>
      <View style={styles.container}>
        <Text style={styles.title}>Password reset successful</Text>
        {error ? <Text style={[styles.msg, { color: '#991B1B' }]}>{error}</Text> : null}
        <Text style={styles.subtitle}>You can now continue to your dashboard.</Text>
        <TouchableOpacity style={styles.primaryBtn} onPress={() => nav.replace('Login')}>
          <Text style={styles.primaryBtnText}>Go to Login</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flex: 1, paddingHorizontal: 24, paddingTop: 16 },
  title: { fontSize: 22, fontWeight: '700', color: '#0B0F1A', marginBottom: 8 },
  subtitle: { color: '#0B0F1A', marginBottom: 16 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center', marginTop: 8 },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  msg: { marginBottom: 8 },
});
