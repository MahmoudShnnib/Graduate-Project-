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
  return config;
});

let onUnauthorized: (() => void) | null = null;
export function registerUnauthorizedHandler(fn: () => void) {
  onUnauthorized = fn;
}

apiClient.interceptors.response.use(
  (r) => r,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      await clearToken();
      onUnauthorized?.();
    }
    return Promise.reject(error);
  }
);
