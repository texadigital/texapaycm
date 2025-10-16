import axios, { AxiosInstance } from 'axios';
import { getAccessToken } from './auth';

const BASE_URL = process.env.EXPO_PUBLIC_API_BASE_URL as string;

export const http: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: 30000,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

function newIdemKey() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

http.interceptors.request.use(async (config) => {
  const headers: any = config.headers ?? {};
  if (typeof headers.set === 'function') {
    if (!headers.get?.('Idempotency-Key')) headers.set('Idempotency-Key', newIdemKey());
  } else {
    if (!headers['Idempotency-Key']) headers['Idempotency-Key'] = newIdemKey();
  }
  const access = await getAccessToken();
  if (access) {
    if (typeof headers.set === 'function') headers.set('Authorization', `Bearer ${access}`);
    else headers['Authorization'] = `Bearer ${access}`;
  }
  config.headers = headers;
  return config;
});

export default http;
