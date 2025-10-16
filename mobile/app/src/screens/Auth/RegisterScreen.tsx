import React, { useMemo, useRef, useState } from 'react';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import type { RootStackParamList } from '../../navigation/AppNavigator';
import {
  View,
  Text,
  SafeAreaView,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  Platform,
  StatusBar,
  useWindowDimensions,
  ScrollView,
  KeyboardAvoidingView,
} from 'react-native';
import Logo from '../../../assets/logo.svg';
import { Eye, EyeOff } from 'lucide-react-native';
import http from '../../lib/http';
import { setAccessToken } from '../../lib/auth';

const BASE_WIDTH = 375;

export default function RegisterScreen() {
  const { width, height } = useWindowDimensions();
  const scale = Math.max(0.85, Math.min(1.25, width / BASE_WIDTH));
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const metrics = useMemo(() => {
    const pagePaddingH = Math.round(Math.max(24, Math.min(44, width * 0.10))); // match onboarding
    const ctaPaddingH = Math.round(Math.max(16, Math.min(28, width * 0.06))); // match onboarding
    const ctaBottom = Math.round(Math.max(56, Math.min(96, height * 0.08))); // match onboarding
    const titleFont = Math.round(Math.max(24, Math.min(32, 28 * scale)));
    const fieldHeight = Math.round(Math.max(48, Math.min(56, 52 * scale)));
    const gap = Math.round(Math.max(12, Math.min(20, 16 * scale)));
    return { pagePaddingH, ctaPaddingH, ctaBottom, titleFont, fieldHeight, gap };
  }, [width, height, scale]);

  type Step = 'phone'|'name'|'password'|'pin';
  const [step, setStep] = useState<Step>('phone');
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [showPass, setShowPass] = useState(false);
  const [pin, setPin] = useState('');
  const [pinConfirm, setPinConfirm] = useState('');
  const pinRefs = useRef<Array<TextInput | null>>([null, null, null, null]);
  const pinCRefs = useRef<Array<TextInput | null>>([null, null, null, null]);
  const setPinRef = (i: number) => (el: TextInput | null): void => { pinRefs.current[i] = el; };
  const setPinCRef = (i: number) => (el: TextInput | null): void => { pinCRefs.current[i] = el; };
  const [submitting, setSubmitting] = useState(false);
  const [topError, setTopError] = useState<string | null>(null);
  const isValidPhone = phone.trim().length >= 9;
  const isValidName = fullName.trim().length >= 2;
  const isValidPass = password.trim().length >= 6;
  const isValidPin = /^\d{4}$/.test(pin) && pin === pinConfirm;

  const normPhone = (p: string) => {
    const d = p.replace(/\D+/g, '');
    if (d.startsWith('237')) return '+'+d;
    if (d.length === 9 && d.startsWith('6')) return '+237'+d;
    return d ? '+'+d : '';
  };

  const primaryAction = async () => {
    if (step === 'phone') {
      if (!isValidPhone) return;
      setStep('name');
      return;
    }
    if (step === 'name') {
      if (!isValidName) return;
      setStep('password');
      return;
    }
    if (step === 'password') {
      if (!isValidPass) return;
      setStep('pin');
      return;
    }
    if (step === 'pin') {
      if (!isValidPin) return;
      try {
        setSubmitting(true);
        setTopError(null);
        const payload: any = {
          phone: normPhone(phone),
          password,
          pin,
          // send both keys to satisfy possible server expectations
          name: fullName,
          full_name: fullName,
        };
        const res = await http.post('/api/mobile/auth/register', payload);
        const token = res?.data?.access_token || res?.data?.token || res?.data?.access || res?.data?.jwt || res?.data?.data?.token || null;
        if (token) {
          await setAccessToken(token);
        } else {
          // Legacy register may not return a token; perform an explicit login
          const loginRes = await http.post('/api/mobile/auth/login', { phone: normPhone(phone), password });
          const ltok = loginRes?.data?.access_token || loginRes?.data?.token || loginRes?.data?.jwt || loginRes?.data?.data?.token || null;
          if (ltok) await setAccessToken(ltok);
        }
        // Product decision: After registration, always present Privacy consent
        // even if backend reports it's already accepted, to match UX requirement.
        navigation.replace('Privacy');
      } catch (e: any) {
        const msg = e?.response?.data?.message || e?.message || 'Registration failed';
        setTopError(String(msg));
      } finally {
        setSubmitting(false);
      }
    }
  };

  return (
    <SafeAreaView style={[styles.safeArea, { paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight ?? 0) + 2 : 0 }]}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={[styles.container, { paddingHorizontal: metrics.pagePaddingH }]}
          keyboardShouldPersistTaps="handled">
          {topError ? (
            <View style={styles.errorBar}>
              <Text style={styles.errorText}>{topError}</Text>
            </View>
          ) : null}
          <View style={styles.brandWrap}>
            <Logo width={128} height={40} preserveAspectRatio="xMidYMid meet" />
          </View>
          <View style={styles.headerWrap}>
            <Text style={[styles.title, { fontSize: Math.max(18, Math.min(22, 20 * scale)), fontWeight: '500' }]}>
              {step==='pin' ? 'Create your PIN code' : 'Get Started on Texa'}
            </Text>
            {step==='pin' ? (
              <Text style={styles.subtitleMuted}>Please create your PIN code for login and secured payments</Text>
            ) : null}
          </View>

          {/* Step content matching the sample layout; only the field label/input changes */}
          {step === 'phone' && (
            <>
              <Text style={styles.label}>Phone</Text>
              <View style={[styles.inputRow, { height: metrics.fieldHeight }]}> 
                <View style={styles.flagCm}>
                  <View style={[styles.flagCmBar, { backgroundColor: '#007A5E' }]} />
                  <View style={[styles.flagCmBar, { backgroundColor: '#CE1126' }]} />
                  <View style={[styles.flagCmBar, { backgroundColor: '#FCD116' }]} />
                </View>
                <TextInput
                  style={styles.inputRowField}
                  value={phone}
                  onChangeText={setPhone}
                  placeholder="Enter your phone number"
                  placeholderTextColor="#9CA3AF"
                  keyboardType="phone-pad"
                  returnKeyType="done"
                />
              </View>
            </>
          )}
          {step === 'pin' && (
            <>
              <Text style={styles.label}>Enter PIN Code</Text>
              <View style={styles.pinRow}>
                {[0,1,2,3].map((i) => (
                  <View key={i} style={styles.pinBox}>
                    <TextInput
                      ref={setPinRef(i)}
                      style={styles.pinInput}
                      value={pin[i] || ''}
                      onChangeText={(t) => {
                        const d = (t || '').replace(/\D+/g, '').slice(-1);
                        const next = (pin.slice(0, i) + d + pin.slice(i+1)).padEnd(4, '');
                        setPin(next);
                        if (d && i < 3) pinRefs.current[i+1]?.focus();
                      }}
                      onKeyPress={({ nativeEvent }) => {
                        if (nativeEvent.key === 'Backspace' && !pin[i] && i > 0) pinRefs.current[i-1]?.focus();
                      }}
                      keyboardType="number-pad"
                      maxLength={1}
                      secureTextEntry
                    />
                  </View>
                ))}
              </View>
              <Text style={[styles.label, { marginTop: 14 }]}>Confirm PIN</Text>
              <View style={styles.pinRow}>
                {[0,1,2,3].map((i) => (
                  <View key={i} style={styles.pinBox}>
                    <TextInput
                      ref={setPinCRef(i)}
                      style={styles.pinInput}
                      value={pinConfirm[i] || ''}
                      onChangeText={(t) => {
                        const d = (t || '').replace(/\D+/g, '').slice(-1);
                        const next = (pinConfirm.slice(0, i) + d + pinConfirm.slice(i+1)).padEnd(4, '');
                        setPinConfirm(next);
                        if (d && i < 3) pinCRefs.current[i+1]?.focus();
                      }}
                      onKeyPress={({ nativeEvent }) => {
                        if (nativeEvent.key === 'Backspace' && !pinConfirm[i] && i > 0) pinCRefs.current[i-1]?.focus();
                      }}
                      keyboardType="number-pad"
                      maxLength={1}
                      secureTextEntry
                    />
                  </View>
                ))}
              </View>
            </>
          )}
          {step === 'name' && (
            <>
              <Text style={styles.label}>Full name</Text>
              <TextInput
                value={fullName}
                onChangeText={setFullName}
                placeholder="Enter your full name"
                placeholderTextColor="#9CA3AF"
                style={[styles.inputSoft, { height: metrics.fieldHeight }]}
                autoCapitalize="words"
                returnKeyType="done"
              />
            </>
          )}
          {step === 'password' && (
            <>
              <Text style={styles.label}>Password</Text>
              <View style={[styles.inputPwdRow, { height: metrics.fieldHeight }]}> 
                <TextInput
                  style={styles.inputPwdField}
                  value={password}
                  onChangeText={setPassword}
                  placeholder="Create a password"
                  placeholderTextColor="#9CA3AF"
                  secureTextEntry={!showPass}
                  returnKeyType="done"
                />
                <TouchableOpacity accessibilityRole="button" onPress={() => setShowPass(v => !v)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                  {showPass ? <EyeOff size={20} color="#6B7280" /> : <Eye size={20} color="#6B7280" />}
                </TouchableOpacity>
              </View>
            </>
          )}
        </ScrollView>
      </KeyboardAvoidingView>

      {/* Static CTAs at bottom, same position as Onboarding */}
      <View style={[styles.ctaWrap, { marginBottom: metrics.ctaBottom, paddingHorizontal: metrics.ctaPaddingH }]}>
        <TouchableOpacity
          activeOpacity={0.9}
          style={[
            styles.primaryBtn,
            (step==='phone' && !isValidPhone) || (step==='name' && !isValidName) || (step==='password' && !isValidPass) || (step==='pin' && !isValidPin)
              ? styles.primaryBtnDisabled
              : null,
          ]}
          onPress={primaryAction}
          disabled={submitting || (step==='phone' && !isValidPhone) || (step==='name' && !isValidName) || (step==='password' && !isValidPass) || (step==='pin' && !isValidPin)}
        >
          <Text style={styles.primaryBtnText}>{submitting ? 'Please wait…' : (step==='pin' ? 'Create account' : 'Continue')}</Text>
        </TouchableOpacity>
        <TouchableOpacity activeOpacity={0.8} style={{ alignItems: 'center', marginTop: 12 }} onPress={() => navigation.navigate('Login')}>
          <Text style={styles.linkText}>Already have an account? <Text style={{ textDecorationLine: 'underline' }}>Sign in</Text></Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F6FE',
  },
  container: {
    flexGrow: 1,
    paddingTop: 8,
    paddingBottom: 16,
  },
  headerWrap: {
    alignItems: 'flex-start',
    marginTop: 12,
    marginBottom: 24,
  },
  brandWrap: { alignItems: 'center', marginTop: 0, marginBottom: 8 },
  title: {
    color: '#0B0F1A',
    textAlign: 'left',
    lineHeight: 32,
    fontWeight: '700',
  },
  subtitle: {
    marginTop: 6,
    color: '#0B0F1A',
    textAlign: 'center',
  },
  subtitleMuted: { marginTop: 6, color: '#6B7280' },
  label: {
    marginBottom: 6,
    color: '#0B0F1A',
    fontSize: 14,
    fontWeight: '600',
  },
  em: {
    color: '#1543A6',
    fontWeight: '600',
  },
  input: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E8ECF8',
    paddingHorizontal: 16,
    color: '#0B0F1A',
  },
  inputSoft: {
    backgroundColor: '#F3F4F6',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E8ECF8',
    paddingHorizontal: 16,
    color: '#0B0F1A',
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E8ECF8',
    paddingHorizontal: 12,
  },
  inputPwdRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F3F4F6',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E8ECF8',
    paddingHorizontal: 12,
  },
  inputRowField: {
    flex: 1,
    color: '#0B0F1A',
    paddingLeft: 8,
  },
  inputPwdField: {
    flex: 1,
    color: '#0B0F1A',
  },
  pinRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 4,
  },
  pinBox: {
    width: 48,
    height: 48,
    borderRadius: 10,
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#E8ECF8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  pinInput: {
    width: '100%',
    textAlign: 'center',
    fontSize: 18,
    color: '#0B0F1A',
    paddingVertical: 0,
  },
  flagCm: {
    width: 22,
    height: 14,
    borderRadius: 2,
    overflow: 'hidden',
    flexDirection: 'row',
  },
  flagCmBar: { flex: 1 },
  ctaWrap: {},
  primaryBtn: {
    backgroundColor: '#1543A6',
    borderRadius: 24,
    paddingVertical: 16,
    alignItems: 'center',
  },
  primaryBtnDisabled: {
    backgroundColor: '#D6E2FF',
  },
  primaryBtnText: {
    color: '#FFFFFF',
    fontWeight: '500',
  },
  linkText: { color: '#1543A6', fontWeight: '500' },
  errorBar: {
    backgroundColor: '#FEE2E2',
    borderColor: '#FCA5A5',
    borderWidth: 1,
    borderRadius: 10,
    paddingVertical: 10,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  errorText: {
    color: '#991B1B',
  },
});

