import { useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { createContext, ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { apiClient, registerUnauthorizedHandler } from '@/api/client';
import type { Student } from '@/api/types';
import { clearToken, getToken, setToken } from '@/lib/secureStore';

type AuthState =
  | { status: 'loading' }
  | { status: 'authenticated'; student: Student }
  | { status: 'unauthenticated' };

type AuthContextValue = {
  state: AuthState;
  login: (enrollmentNo: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshMe: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AuthState>({ status: 'loading' });
  const qc = useQueryClient();

  const refreshMe = useCallback(async () => {
    try {
      const { data } = await apiClient.get<{ data: Student }>('/auth/me');
      setState({ status: 'authenticated', student: data.data });
    } catch {
      await clearToken();
      setState({ status: 'unauthenticated' });
    }
  }, []);

  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (!token) {
        setState({ status: 'unauthenticated' });
        return;
      }
      await refreshMe();
    })();
  }, [refreshMe]);

  useEffect(() => {
    registerUnauthorizedHandler(() => {
      qc.clear();
      setState({ status: 'unauthenticated' });
      router.replace('/(auth)/login');
    });
  }, [qc]);

  const login = useCallback(
    async (enrollmentNo: string, password: string) => {
      const { data } = await apiClient.post<{ token: string; student: Student }>('/auth/login', {
        enrollment_no: enrollmentNo,
        password,
        device_name: 'mobile',
      });
      await setToken(data.token);
      setState({ status: 'authenticated', student: data.student });
    },
    []
  );

  const logout = useCallback(async () => {
    try {
      await apiClient.post('/auth/logout');
    } catch {
      // ignore
    }
    await clearToken();
    qc.clear();
    setState({ status: 'unauthenticated' });
  }, [qc]);

  const value = useMemo(() => ({ state, login, logout, refreshMe }), [state, login, logout, refreshMe]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
