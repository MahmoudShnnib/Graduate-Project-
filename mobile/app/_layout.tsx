import '../global.css';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useFonts } from 'expo-font';
import { Stack, useRouter, useSegments } from 'expo-router';
import * as SystemUI from 'expo-system-ui';
import { useEffect } from 'react';
import { ActivityIndicator, I18nManager, View } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { AuthProvider, useAuth } from '@/hooks/useAuth';

if (!I18nManager.isRTL) {
  try {
    I18nManager.allowRTL(true);
    I18nManager.forceRTL(true);
  } catch {
    // ignore
  }
}

SystemUI.setBackgroundColorAsync('#F8FAFC').catch(() => null);

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      staleTime: 30_000,
      refetchOnWindowFocus: false,
    },
  },
});

function AuthGate({ children }: { children: React.ReactNode }) {
  const auth = useAuth();
  const router = useRouter();
  const segments = useSegments();

  useEffect(() => {
    if (auth.state.status === 'loading') return;
    const inAuth = segments[0] === '(auth)';
    const inDesignPreview = segments[0] === '_design-preview';

    if (auth.state.status === 'unauthenticated' && !inAuth && !inDesignPreview) {
      router.replace('/(auth)/login');
    } else if (auth.state.status === 'authenticated' && inAuth) {
      router.replace('/(tabs)');
    }
  }, [auth.state.status, segments, router]);

  if (auth.state.status === 'loading') {
    return (
      <View className="flex-1 items-center justify-center bg-bg">
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }
  return <>{children}</>;
}

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    'NotoKufiArabic-Regular': require('../assets/fonts/NotoKufiArabic-Regular.ttf'),
    'NotoKufiArabic-Medium': require('../assets/fonts/NotoKufiArabic-Medium.ttf'),
    'NotoKufiArabic-Bold': require('../assets/fonts/NotoKufiArabic-Bold.ttf'),
  });

  if (!fontsLoaded) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#F8FAFC' }}>
        <ActivityIndicator size="large" color="#2563EB" />
      </View>
    );
  }

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <AuthProvider>
            <AuthGate>
              <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: '#F8FAFC' } }}>
                <Stack.Screen name="(auth)" />
                <Stack.Screen name="(tabs)" />
                <Stack.Screen name="_design-preview" options={{ headerShown: false }} />
              </Stack>
            </AuthGate>
          </AuthProvider>
        </QueryClientProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
