import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, TouchableOpacity, Platform, StatusBar, ScrollView } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import http from '../../lib/http';
import { getAccessToken } from '../../lib/auth';

export default function PrivacyConsentScreen({ navigation }: NativeStackScreenProps<RootStackParamList, 'Privacy'>) {
  const [loading, setLoading] = React.useState(true);
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        setError(null);
        const token = await getAccessToken();
        const res = await http.get('/api/mobile/policies/status', token ? { headers: { Authorization: `Bearer ${token}` } } : undefined);
        if (!mounted) return;
        if (res?.data?.accepted) {
          navigation.replace('Dashboard');
        }
      } catch (e: any) {
        // non-fatal; allow manual accept
      } finally {
        if (mounted) setLoading(false);
      }
    })();
    return () => { mounted = false; };
  }, [navigation]);

  const accept = async () => {
    try {
      setSubmitting(true);
      setError(null);
      const token = await getAccessToken();
      await http.post('/api/mobile/policies/accept', {}, token ? { headers: { Authorization: `Bearer ${token}` } } : undefined);
      navigation.replace('Dashboard');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Unable to accept policies');
    } finally {
      setSubmitting(false);
    }
  };
  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }] }>
      <ScrollView contentContainerStyle={styles.container}>
        <View style={styles.headerWrap}>
          <Text style={styles.title}>Privacy & Policy</Text>
          <Text style={styles.subtitle}>Please review and accept to continue.</Text>
        </View>

        <View style={styles.card}>
          {error ? (
            <View style={styles.errorBar}><Text style={styles.errorText}>{error}</Text></View>
          ) : null}
          <Text style={styles.body}>By creating an account, you agree to our Privacy Policy and Terms. We securely process your data to enable transfers from Cameroon to Nigeria. You can request data deletion at any time.</Text>
        </View>

        <View style={styles.ctaWrap}>
          <TouchableOpacity style={[styles.primaryBtn, submitting && styles.primaryBtnDisabled]} activeOpacity={0.9} onPress={accept} disabled={submitting || loading}>
            <Text style={styles.primaryBtnText}>{submitting ? 'Please wait…' : 'I accept'}</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flexGrow: 1, paddingHorizontal: 24, paddingTop: 16 },
  headerWrap: { alignItems: 'center', marginBottom: 12 },
  title: { fontSize: 20, fontWeight: '600', color: '#0B0F1A' },
  subtitle: { marginTop: 6, color: '#0B0F1A' },
  card: { backgroundColor: '#FFFFFF', borderRadius: 12, borderWidth: 1, borderColor: '#E8ECF8', padding: 16 },
  body: { color: '#0B0F1A', lineHeight: 20 },
  ctaWrap: { marginTop: 24 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 999, paddingVertical: 16, alignItems: 'center' },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  errorBar: { backgroundColor: '#FEE2E2', borderColor: '#FCA5A5', borderWidth: 1, borderRadius: 10, paddingVertical: 10, paddingHorizontal: 12, marginBottom: 12 },
  errorText: { color: '#991B1B' },
});
