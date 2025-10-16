import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import Onboarding from '../screens/Onboarding/Onboarding';
import RegisterScreen from '../screens/Auth/RegisterScreen';
import PrivacyConsentScreen from '../screens/Auth/PrivacyConsentScreen';
import DashboardScreen from '../screens/Dashboard/DashboardScreen';
import LoginScreen from '../screens/Auth/LoginScreen';
import ProfileScreen from '../screens/Profile/ProfileScreen';
import SecurityScreen from '../screens/Profile/SecurityScreen';
import PasswordForgotScreen from '../screens/Auth/PasswordForgotScreen';
import PasswordResetScreen from '../screens/Auth/PasswordResetScreen';
import PasswordOtpScreen from '../screens/Auth/PasswordOtpScreen';
import PasswordNewScreen from '../screens/Auth/PasswordNewScreen';
import PasswordDoneScreen from '../screens/Auth/PasswordDoneScreen';
import TransfersStartScreen from '../screens/Transfers/TransfersStartScreen';
import TransfersHistoryScreen from '../screens/Transfers/TransfersHistoryScreen';
import TransferVerifyScreen from '../screens/Transfers/TransferVerifyScreen';
import TransferQuoteScreen from '../screens/Transfers/TransferQuoteScreen';
import TransferConfirmScreen from '../screens/Transfers/TransferConfirmScreen';
import TransferProcessingScreen from '../screens/Transfers/TransferProcessingScreen';
import TransferTimelineScreen from '../screens/Transfers/TransferTimelineScreen';

export type RootStackParamList = {
  Onboarding: undefined;
  Register: undefined;
  Login: undefined;
  Privacy: undefined;
  Dashboard: undefined;
  Profile: undefined;
  Security: undefined;
  PasswordForgot: undefined;
  PasswordReset: { phone?: string } | undefined;
  PasswordOtp: { phone: string };
  PasswordNew: { phone: string; code: string };
  PasswordDone: { phone: string; password: string } | undefined;
  TransfersStart: undefined;
  TransfersHistory: undefined;
  TransferVerify: undefined;
  TransferQuote: { recipient: { bankCode: string; bankName: string; account: string; accountName: string } } | undefined;
  TransferConfirm: { quoteId: string } | undefined;
  TransferProcessing: { transferId: number } | undefined;
  TransferTimeline: { transferId: number };
};

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function AppNavigator() {
  return (
    <NavigationContainer>
      <Stack.Navigator
        initialRouteName="Onboarding"
        screenOptions={{
          headerShadowVisible: false,
          headerTitleStyle: { color: '#0B0F1A' },
          contentStyle: { backgroundColor: '#F4F6FE' },
        }}
      >
        <Stack.Screen name="Onboarding" component={Onboarding} options={{ headerShown: false }} />
        <Stack.Screen name="Register" component={RegisterScreen} options={{ title: 'Create account' }} />
        <Stack.Screen name="Privacy" component={PrivacyConsentScreen} options={{ title: 'Privacy & Policy' }} />
        <Stack.Screen name="Dashboard" component={DashboardScreen} options={{ headerShown: false }} />
        <Stack.Screen name="Login" component={LoginScreen} options={{ title: 'Login' }} />
        <Stack.Screen name="Profile" component={ProfileScreen} options={{ title: 'Profile' }} />
        <Stack.Screen name="Security" component={SecurityScreen} options={{ title: 'Security' }} />
        <Stack.Screen name="PasswordForgot" component={PasswordForgotScreen} options={{ title: 'Forgot Password' }} />
        <Stack.Screen name="PasswordReset" component={PasswordResetScreen} options={{ title: 'Reset Password' }} />
        <Stack.Screen name="PasswordOtp" component={PasswordOtpScreen} options={{ title: 'Forgot Password' }} />
        <Stack.Screen name="PasswordNew" component={PasswordNewScreen} options={{ title: 'New Password' }} />
        <Stack.Screen name="PasswordDone" component={PasswordDoneScreen} options={{ title: 'Password Reset' }} />
        <Stack.Screen name="TransfersStart" component={TransfersStartScreen} options={{ title: 'Start Transfer' }} />
        <Stack.Screen name="TransfersHistory" component={TransfersHistoryScreen} options={{ title: 'Transfer History' }} />
        <Stack.Screen name="TransferVerify" component={TransferVerifyScreen} options={{ title: 'Verify Recipient' }} />
        <Stack.Screen name="TransferQuote" component={TransferQuoteScreen} options={{ title: 'Quote' }} />
        <Stack.Screen name="TransferConfirm" component={TransferConfirmScreen} options={{ title: 'Confirm' }} />
        <Stack.Screen name="TransferProcessing" component={TransferProcessingScreen} options={{ title: 'Processing' }} />
        <Stack.Screen name="TransferTimeline" component={TransferTimelineScreen} options={{ title: 'Transfer Details' }} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}

