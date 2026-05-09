import { zodResolver } from '@hookform/resolvers/zod';
import { GraduationCap } from 'lucide-react-native';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { KeyboardAvoidingView, Platform, ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { z } from 'zod';

import { Button, Input } from '@/components/primitives';
import { useAuth } from '@/hooks/useAuth';

const schema = z.object({
  enrollment_no: z.string().min(2, 'رقم القيد مطلوب'),
  password: z.string().min(1, 'كلمة المرور مطلوبة'),
});
type Form = z.infer<typeof schema>;

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
    try {
      await login(values.enrollment_no, values.password);
    } catch (err: any) {
      const message =
        err?.response?.data?.errors?.enrollment_no?.[0] ||
        err?.response?.data?.message ||
        'تعذر تسجيل الدخول. تحقق من البيانات وحاول مجدداً.';
      setSubmitError(message);
    }
  }

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-bg"
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={{ paddingTop: insets.top }}
    >
      <ScrollView
        contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24 }}
        keyboardShouldPersistTaps="handled"
      >
        <View className="items-center mb-10">
          <View className="w-20 h-20 rounded-3xl bg-primary items-center justify-center mb-4">
            <GraduationCap size={40} color="#FFF" />
          </View>
          <Text className="font-kufi-bold text-ink text-[24px]">بوابة الطالب</Text>
          <Text className="font-kufi text-muted text-[14px] mt-2 text-center">
            ادخل بيانات حسابك للاطلاع على درجاتك ومقرراتك
          </Text>
        </View>

        <View className="gap-y-4">
          <Controller
            control={control}
            name="enrollment_no"
            render={({ field, fieldState }) => (
              <Input
                label="رقم القيد"
                value={field.value}
                onChangeText={field.onChange}
                autoCapitalize="characters"
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

        <Text className="font-kufi text-muted text-[12px] text-center mt-8">
          للمساعدة تواصل مع شؤون الطلاب
        </Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
