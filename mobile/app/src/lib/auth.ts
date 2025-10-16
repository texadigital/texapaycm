import * as SecureStore from 'expo-secure-store';

const ACCESS_TOKEN_KEY = 'access_token';

export async function getAccessToken(): Promise<string | null> {
  try { return (await SecureStore.getItemAsync(ACCESS_TOKEN_KEY)) || null; } catch { return null; }
}

export async function setAccessToken(token: string | null): Promise<void> {
  try {
    if (token) {
      await SecureStore.setItemAsync(ACCESS_TOKEN_KEY, token);
    } else {
      await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
    }
  } catch {}
}
