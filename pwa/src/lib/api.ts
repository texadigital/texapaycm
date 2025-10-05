import axios, { AxiosInstance, InternalAxiosRequestConfig, AxiosError } from 'axios';
import { getAccessToken, setAccessToken, refreshAccessToken } from './auth';

// Base URL strategy:
// - In DEVELOPMENT (browser), ALWAYS use relative base ('') so requests go to /api and
//   get proxied by Next.js rewrites() to the Laravel backend. This avoids CORS/LAN issues.
// - In PRODUCTION (or during SSR/build), use NEXT_PUBLIC_API_BASE_URL.
const isBrowser = typeof window !== 'undefined';
const isDev = process.env.NODE_ENV === 'development';
const BASE_URL = (isBrowser && isDev) ? '' : (process.env.NEXT_PUBLIC_API_BASE_URL || '');

export const http: AxiosInstance = axios.create({
  // If BASE_URL is empty, axios will use same-origin relative URLs
  baseURL: BASE_URL,
  withCredentials: true,
  // More tolerant timeout for tunnels (ngrok) and first-time warmups
  timeout: 30000,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Generate a UUID v4 (simple impl for idempotency keys)
export function newIdemKey() {
  // Not crypto-strong, sufficient for client idempotency header
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

// Attach Idempotency-Key automatically and ensure Authorization header is set.
// Axios v1 uses AxiosHeaders which requires using .set() for reliability.
http.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
  const headers = (config.headers ?? {}) as any;

  // Ensure Idempotency-Key exists on every request (safe for GET too)
  if (typeof headers.set === 'function') {
    // AxiosHeaders
    if (!headers.get?.('Idempotency-Key')) {
      headers.set('Idempotency-Key', newIdemKey());
    }
  } else {
    if (!headers['Idempotency-Key']) {
      headers['Idempotency-Key'] = newIdemKey();
    }
  }

  // Attach bearer if present
  const access = getAccessToken();
  if (access) {
    if (typeof headers.set === 'function') {
      headers.set('Authorization', `Bearer ${access}`);
    } else {
      headers['Authorization'] = `Bearer ${access}`;
    }
  }

  // In case headers was initially undefined, put it back on config
  config.headers = headers;
  return config;
});

// Auto-refresh on 401 once
let isRefreshing = false;
let pendingRequests: Array<() => void> = [];

http.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const original = error.config as any;
    if (error.response?.status === 401 && !original?._retry) {
      original._retry = true;
      try {
        if (!isRefreshing) {
          isRefreshing = true;
          const newToken = await refreshAccessToken();
          setAccessToken(newToken || null);
          isRefreshing = false;
          pendingRequests.forEach((cb) => cb());
          pendingRequests = [];
        } else {
          await new Promise<void>((resolve) => pendingRequests.push(resolve));
        }
        // retry
        const access = getAccessToken();
        if (access && original?.headers) {
          original.headers['Authorization'] = `Bearer ${access}`;
        }
        return http(original);
      } catch (e) {
        isRefreshing = false;
        pendingRequests = [];
        // Notify app that auth is no longer valid
        if (typeof window !== 'undefined') {
          window.dispatchEvent(new CustomEvent('auth:unauthorized'));
        }
      }
    }
    throw error;
  }
);

export default http;
