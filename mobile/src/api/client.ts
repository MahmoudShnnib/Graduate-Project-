import axios, { AxiosError, AxiosInstance } from 'axios';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

import { clearToken, getToken } from '@/lib/secureStore';

function resolveBaseUrl(): string {
  const fromEnv = Constants.expoConfig?.extra?.apiUrl as string | undefined;
  if (fromEnv) return fromEnv;

  const debuggerHost = Constants.expoConfig?.hostUri ?? Constants.expoGoConfig?.debuggerHost;
  if (debuggerHost) {
    const host = debuggerHost.split(':')[0];
    return `http://${host}:8000/api/v1`;
  }

  if (Platform.OS === 'android') return 'http://10.0.2.2:8000/api/v1';
  return 'http://127.0.0.1:8000/api/v1';
}

export const apiBaseUrl = resolveBaseUrl();

if (__DEV__) {
  console.log('[API] base URL =', apiBaseUrl);
}

export const apiClient: AxiosInstance = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

apiClient.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  if (__DEV__) {
    console.log('[API] →', config.method?.toUpperCase(), (config.baseURL ?? '') + (config.url ?? ''), config.data ?? '');
  }
  return config;
});

let onUnauthorized: (() => void) | null = null;
export function registerUnauthorizedHandler(fn: () => void) {
  onUnauthorized = fn;
}

apiClient.interceptors.response.use(
  (r) => {
    if (__DEV__) {
      console.log('[API] ←', r.status, r.config.url);
    }
    return r;
  },
  async (error: AxiosError) => {
    if (__DEV__) {
      console.log('[API] ✗', error.response?.status, error.config?.url, error.response?.data ?? error.message);
    }
    if (error.response?.status === 401) {
      await clearToken();
      onUnauthorized?.();
    }
    return Promise.reject(error);
  }
);
