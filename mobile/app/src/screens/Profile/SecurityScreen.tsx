import React from 'react';
import { SafeAreaView, View, Text, StyleSheet, Platform, StatusBar, TextInput, TouchableOpacity, ScrollView, Switch } from 'react-native';
import http from '../../lib/http';

export default function SecurityScreen() {
  const [savingPin, setSavingPin] = React.useState(false);
  const [savingPwd, setSavingPwd] = React.useState(false);
  const [savingToggles, setSavingToggles] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [ok, setOk] = React.useState<string | null>(null);

  const [pin1, setPin1] = React.useState('');
  const [pin2, setPin2] = React.useState('');

  const [currentPassword, setCurrentPassword] = React.useState('');
  const [newPassword, setNewPassword] = React.useState('');

  const [biometric, setBiometric] = React.useState(false);
  const [notifications, setNotifications] = React.useState(true);

  const validPin = /^\d{4}$/.test(pin1) && pin1 === pin2;

  const savePin = async () => {
    if (!validPin) return;
    try {
      setSavingPin(true); setError(null); setOk(null);
      await http.post('/api/mobile/profile/security/pin', { pin: pin1 });
      setOk('PIN updated');
      setPin1(''); setPin2('');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to update PIN');
    } finally {
      setSavingPin(false);
    }
  };

  const savePassword = async () => {
    if (!currentPassword || !newPassword) return;
    try {
      setSavingPwd(true); setError(null); setOk(null);
      await http.post('/api/mobile/profile/security/password', { current_password: currentPassword, password: newPassword });
      setOk('Password updated');
      setCurrentPassword(''); setNewPassword('');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to update password');
    } finally {
      setSavingPwd(false);
    }
  };

  const saveToggles = async () => {
    try {
      setSavingToggles(true); setError(null); setOk(null);
      await http.post('/api/mobile/profile/security/toggles', {
        biometric_enabled: biometric,
        notifications_enabled: notifications,
      });
      setOk('Security settings saved');
    } catch (e: any) {
      setError(e?.response?.data?.message || e.message || 'Failed to save settings');
    } finally {
      setSavingToggles(false);
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 8 : 0 }]}>
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.title}>Security</Text>
        {error ? <Text style={[styles.msg, { color: '#991B1B' }]}>{error}</Text> : null}
        {ok ? <Text style={[styles.msg, { color: '#166534' }]}>{ok}</Text> : null}

        <Text style={styles.section}>Update PIN</Text>
        <Text style={styles.label}>New PIN</Text>
        <TextInput style={styles.input} value={pin1} onChangeText={(t) => setPin1(t.replace(/\D+/g, '').slice(0,4))} secureTextEntry keyboardType="number-pad" placeholder="••••" placeholderTextColor="#9CA3AF" />
        <Text style={styles.label}>Confirm PIN</Text>
        <TextInput style={styles.input} value={pin2} onChangeText={(t) => setPin2(t.replace(/\D+/g, '').slice(0,4))} secureTextEntry keyboardType="number-pad" placeholder="••••" placeholderTextColor="#9CA3AF" />
        <TouchableOpacity style={[styles.primaryBtn, (!validPin || savingPin) && styles.primaryBtnDisabled]} disabled={!validPin || savingPin} onPress={savePin}>
          <Text style={styles.primaryBtnText}>{savingPin ? 'Please wait…' : 'Save PIN'}</Text>
        </TouchableOpacity>

        <Text style={styles.section}>Change Password</Text>
        <Text style={styles.label}>Current password</Text>
        <TextInput style={styles.input} value={currentPassword} onChangeText={setCurrentPassword} secureTextEntry placeholder="Current password" placeholderTextColor="#9CA3AF" />
        <Text style={styles.label}>New password</Text>
        <TextInput style={styles.input} value={newPassword} onChangeText={setNewPassword} secureTextEntry placeholder="New password" placeholderTextColor="#9CA3AF" />
        <TouchableOpacity style={[styles.primaryBtn, (savingPwd || !currentPassword || !newPassword) && styles.primaryBtnDisabled]} disabled={savingPwd || !currentPassword || !newPassword} onPress={savePassword}>
          <Text style={styles.primaryBtnText}>{savingPwd ? 'Please wait…' : 'Save Password'}</Text>
        </TouchableOpacity>

        <Text style={styles.section}>Security Toggles</Text>
        <View style={styles.rowBetween}><Text style={styles.toggleLabel}>Biometric</Text><Switch value={biometric} onValueChange={setBiometric} /></View>
        <View style={styles.rowBetween}><Text style={styles.toggleLabel}>Notifications</Text><Switch value={notifications} onValueChange={setNotifications} /></View>
        <TouchableOpacity style={[styles.primaryBtn, savingToggles && styles.primaryBtnDisabled]} disabled={savingToggles} onPress={saveToggles}>
          <Text style={styles.primaryBtnText}>{savingToggles ? 'Please wait…' : 'Save Settings'}</Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F4F6FE' },
  container: { flexGrow: 1, paddingHorizontal: 24, paddingTop: 16, paddingBottom: 24 },
  title: { fontSize: 20, fontWeight: '600', color: '#0B0F1A', marginBottom: 12 },
  section: { marginTop: 16, marginBottom: 8, color: '#0B0F1A', fontSize: 16, fontWeight: '600' },
  label: { marginTop: 8, marginBottom: 6, color: '#0B0F1A', fontSize: 14, fontWeight: '600' },
  input: { backgroundColor: '#F3F4F6', borderRadius: 10, borderWidth: 1, borderColor: '#E8ECF8', paddingHorizontal: 16, color: '#0B0F1A', height: 52 },
  primaryBtn: { backgroundColor: '#1543A6', borderRadius: 24, paddingVertical: 16, alignItems: 'center', marginTop: 12 },
  primaryBtnDisabled: { backgroundColor: '#D6E2FF' },
  primaryBtnText: { color: '#FFFFFF', fontWeight: '500' },
  msg: { marginBottom: 8 },
  rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginVertical: 8 },
  toggleLabel: { color: '#0B0F1A' },
});
