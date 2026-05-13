import { zodResolver } from '@hookform/resolvers/zod';
import { LinearGradient } from 'expo-linear-gradient';
import { GraduationCap } from 'lucide-react-native';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { KeyboardAvoidingView, Platform, ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { z } from 'zod';

import { apiBaseUrl } from '@/api/client';
import { Button, Input } from '@/components/primitives';
import { useAuth } from '@/hooks/useAuth';
import { haptic } from '@/lib/haptics';
import { colors } from '@/theme/tokens';

const schema = z.object({
  enrollment_no: z.string().min(2, 'رقم القيد مطلوب'),
  password: z.string().min(1, 'كلمة المرور مطلوبة'),
});
type Form = z.infer<typeof schema>;

// Arabic-Indic and Persian digits → Western. Some Arabic keyboards type ٠١٢٣٤٥٦٧٨٩ instead of 0-9.
function normalizeDigits(s: string): string {
  return s
    .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))
    .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0));
}

export default function LoginScreen() {
  const insets = useSafeAreaInsets();
  const { login } = useAuth();
  const [submitError, setSubmitError] = useState<string | null>(null);

  const {
    control,
    handleSubmit,
    formState: { isSubmitting },
  } = useForm<Form>({
    resolver: zodResolver(schema),
    defaultValues: { enrollment_no: '', password: '' },
  });

  async function onSubmit(values: Form) {
    setSubmitError(null);
    const enrollmentNo = normalizeDigits(values.enrollment_no.trim()).toUpperCase();
    const password = values.password;
    try {
      await login(enrollmentNo, password);
      haptic.success();
    } catch (err: any) {
      haptic.error();
      const message =
        err?.response?.data?.errors?.enrollment_no?.[0] ||
        err?.response?.data?.message ||
        'تعذر تسجيل الدخول. تحقق من البيانات وحاول مجدداً.';
      setSubmitError(message);
    }
  }

  return (
    <LinearGradient
      colors={[colors.primary, colors.primaryDark]}
      start={{ x: 0, y: 0 }}
      end={{ x: 0, y: 1 }}
      style={{ flex: 1 }}
    >
      <KeyboardAvoidingView
        className="flex-1"
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={{ paddingTop: insets.top }}
      >
        <ScrollView
          contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24 }}
          keyboardShouldPersistTaps="handled"
        >
          {/* Brand block */}
          <View className="items-center mb-10">
            <View
              className="w-20 h-20 rounded-3xl bg-white items-center justify-center mb-4"
              style={{
                shadowColor: '#000',
                shadowOpacity: 0.18,
                shadowRadius: 16,
                shadowOffset: { width: 0, height: 6 },
                elevation: 6,
              }}
            >
              <GraduationCap size={40} color={colors.primary} />
            </View>
            <Text className="font-kufi-bold text-white text-[32px]">بوابة الطالب</Text>
            <Text className="font-kufi text-white/80 text-[14px] mt-1 text-center">
              نظام إدارة الدراسات العليا
            </Text>
          </View>

          {/* Floating white card */}
          <View
            className="bg-surface rounded-3xl p-6"
            style={{
              shadowColor: '#000',
              shadowOpacity: 0.25,
              shadowRadius: 24,
              shadowOffset: { width: 0, height: 12 },
              elevation: 12,
            }}
          >
            <Text className="font-kufi-bold text-ink text-[18px] mb-1 text-right">تسجيل الدخول</Text>
            <Text className="font-kufi text-muted text-[13px] mb-5 text-right">
              أدخل بيانات حسابك للاطلاع على درجاتك ومقرراتك
            </Text>

            <View className="gap-y-4">
              <Controller
                control={control}
                name="enrollment_no"
                render={({ field, fieldState }) => (
                  <Input
                    label="رقم القيد"
                    value={field.value}
                    onChangeText={(v) => field.onChange(normalizeDigits(v).toUpperCase())}
                    autoCapitalize="characters"
                    autoCorrect={false}
                    keyboardType="ascii-capable"
                    placeholder="مثال: 26CS101"
                    error={fieldState.error?.message}
                  />
                )}
              />
              <Controller
                control={control}
                name="password"
                render={({ field, fieldState }) => (
                  <Input
                    label="كلمة المرور"
                    value={field.value}
                    onChangeText={field.onChange}
                    secureTextEntry
                    autoCapitalize="none"
                    autoCorrect={false}
                    placeholder="••••••••"
                    error={fieldState.error?.message}
                  />
                )}
              />

              {!!submitError && (
                <Text className="font-kufi text-danger text-[13px] text-right">{submitError}</Text>
              )}

              <View className="mt-2">
                <Button
                  label="تسجيل الدخول"
                  size="lg"
                  loading={isSubmitting}
                  onPress={handleSubmit(onSubmit)}
                />
              </View>
            </View>
          </View>

          <Text className="font-kufi text-white/80 text-[12px] text-center mt-8">
            للمساعدة تواصل مع شؤون الطلاب
          </Text>
          {__DEV__ && (
            <Text selectable className="font-kufi text-white/60 text-[10px] text-center mt-2">
              DEV · {apiBaseUrl}
            </Text>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </LinearGradient>
  );
}
