import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity, ScrollView } from 'react-native';
import http from '../../lib/http';

export default function ProfileScreen() {
  const [loading, setLoading] = React.useState(true);
  const [saving, setSaving] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [ok, setOk] = React.useState<string | null>(null);
  const [firstName, setFirstName] = React.useState('');
  const [lastName, setLastName] = React.useState('');
  const [email, setEmail] = React.useState('');

  const load = React.useCallback(async () => {
    try {
      setError(null);
      const res = await http.get('/api/mobile/profile/personal-info');
      const d = res?.data || {};
      setFirstName(d.first_name || d.firstName || '');
      setLastName(d.last_name || d.lastName || '');
      setEmail(d.email || '');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to load');
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => { load(); }, [load]);

  const save = async () => {
    try {
      setSaving(true); setOk(null); setError(null);
      await http.post('/api/mobile/profile/personal-info', {
        first_name: firstName,
        last_name: lastName,
        email,
      });
      setOk('Saved');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to save');
    } finally {
      setSaving(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }] }>
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.title}>Profile</Text>
        {loading ? <Text style={styles.muted}>Loading…</Text> : null}
        {error ? <Text style={[styles.muted, { color: '#991B1B' }]}>{error}</Text> : null}
        {ok ? <Text style={[styles.muted, { color: '#166534' }]}>{ok}</Text> : null}

        <Text style={styles.label}>First name</Text>
        <TextInput style={styles.input} value={firstName} onChangeText={setFirstName} placeholder="First name" placeholderTextColor="#9CA3AF" />
        <Text style={styles.label}>Last name</Text>
        <TextInput style={styles.input} value={lastName} onChangeText={setLastName} placeholder="Last name" placeholderTextColor="#9CA3AF" />
        <Text style={styles.label}>Email</Text>
        <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="Email" placeholderTextColor="#9CA3AF" keyboardType="email-address" />

        <View style={{ height: 12 }} />
        <TouchableOpacity style={[styles.primaryBtn, saving && styles.primaryBtnDisabled]} disabled={saving} onPress={save}>
          <Text style={styles.primaryBtnText}>{saving ? 'Please wait…' : 'Save'}</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flexGrow: 1, paddingHorizontal: 24, paddingTop: 16, paddingBottom: 24 },
  title: { fontSize: 20, fontWeight: '600', color: '#0B0F1A', marginBottom: 12 },
  label: { marginTop: 12, marginBottom: 6, color: '#0B0F1A', fontSize: 14, fontWeight: '600' },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A', height: 52 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center' },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  muted: { color: '#6B7280' },
});
