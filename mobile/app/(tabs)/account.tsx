import {
  Bell,
  BookCheck,
  CalendarClock,
  ChevronLeft,
  GaugeCircle,
  Hash,
  KeyRound,
  LogOut,
  Mail,
  MailQuestion,
  Phone,
  Repeat,
  Shield,
} from 'lucide-react-native';
import { Linking, Pressable, Text, View } from 'react-native';

import { useMe } from '@/api/queries';
import {
  Avatar,
  Button,
  Card,
  IconTile,
  Pill,
  SectionHeader,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { haptic } from '@/lib/haptics';
import { useAuth } from '@/hooks/useAuth';
import { ToneKey, colors, tones } from '@/theme/tokens';

function InfoRow({
  tone,
  icon,
  label,
  value,
}: {
  tone: ToneKey;
  icon: React.ReactNode;
  label: string;
  value: string | number | null | undefined;
}) {
  return (
    <View className="flex-row items-center py-1.5">
      <IconTile tone={tone} size="sm">
        {icon}
      </IconTile>
      <View className="flex-1 ms-3">
        <Text className="font-kufi text-muted text-[11px] text-right">{label}</Text>
        <Text
          className="font-kufi-medium text-ink text-[14px] mt-0.5 text-right"
          numberOfLines={1}
          style={{ fontVariant: ['tabular-nums'] }}
        >
          {value ?? '—'}
        </Text>
      </View>
    </View>
  );
}

function SettingsRow({
  tone,
  icon,
  label,
  hint,
  onPress,
  isLast,
}: {
  tone: ToneKey;
  icon: React.ReactNode;
  label: string;
  hint?: string;
  onPress?: () => void;
  isLast?: boolean;
}) {
  return (
    <Pressable
      onPress={onPress}
      className={`flex-row items-center py-3.5 active:opacity-70 ${isLast ? '' : 'border-b border-border'}`}
    >
      <IconTile tone={tone} size="md" rounded="circle">
        {icon}
      </IconTile>
      <View className="flex-1 ms-3">
        <Text className="font-kufi-medium text-ink text-[14px] text-right">{label}</Text>
        {!!hint && <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">{hint}</Text>}
      </View>
      <ChevronLeft size={20} color={colors.muted} />
    </Pressable>
  );
}

export default function AccountScreen() {
  const { logout } = useAuth();
  const { data: student, isLoading } = useMe();

  return (
    <Screen
      title="الملف الشخصي"
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
    >
      {isLoading || !student ? (
        <SkeletonCard />
      ) : (
        <>
          {/* Profile card overlapping the hero */}
          <Card elevated>
            <View className="flex-row items-center">
              <Avatar name={student.name} size={72} />
              <View className="flex-1 ms-4">
                <Text className="font-kufi-bold text-ink text-[18px] text-right" numberOfLines={1}>
                  {student.name}
                </Text>
                {!!student.specialization && (
                  <Text className="font-kufi text-muted text-[13px] mt-0.5 text-right" numberOfLines={1}>
                    {student.specialization.name}
                  </Text>
                )}
                <View className="flex-row gap-2 mt-2 self-end">
                  <StatusBadge status={student.status} withDot />
                </View>
              </View>
            </View>

            <View className="border-t border-border mt-4 pt-3">
              <InfoRow
                tone="blue"
                icon={<Hash size={16} color={tones.blue.fg} />}
                label="رقم القيد"
                value={student.enrollment_no}
              />
              <InfoRow
                tone="green"
                icon={<Mail size={16} color={tones.green.fg} />}
                label="البريد الإلكتروني"
                value={student.email}
              />
              <InfoRow
                tone="purple"
                icon={<Phone size={16} color={tones.purple.fg} />}
                label="رقم الهاتف"
                value={student.phone}
              />
            </View>
          </Card>

          {/* Academic info */}
          <View className="h-5" />
          <SectionHeader title="المعلومات الأكاديمية" />
          <Card>
            <InfoRow
              tone="sky"
              icon={<CalendarClock size={16} color={tones.sky.fg} />}
              label="سنة القبول"
              value={student.admission_year ?? '—'}
            />
            <InfoRow
              tone="orange"
              icon={<Repeat size={16} color={tones.orange.fg} />}
              label="نوع التسجيل"
              value={student.is_transfer ? 'منتقل' : 'مستجد'}
            />
            <InfoRow
              tone="green"
              icon={<GaugeCircle size={16} color={tones.green.fg} />}
              label="المعدل التراكمي"
              value={Number(student.gpa).toFixed(2)}
            />
          </Card>

          {/* Warnings count */}
          {student.warnings_count > 0 && (
            <>
              <View className="h-5" />
              <SectionHeader title="التحذيرات" />
              <Card className="flex-row items-center">
                <IconTile tone="amber" size="md" rounded="circle">
                  <BookCheck size={18} color={tones.amber.fg} />
                </IconTile>
                <Text className="font-kufi-medium text-ink text-[14px] text-right flex-1 ms-3">
                  لديك {student.warnings_count} تحذير نشط
                </Text>
                <Pill label={String(student.warnings_count)} tone="warning" />
              </Card>
            </>
          )}

          {/* Settings */}
          <View className="h-6" />
          <SectionHeader title="الإعدادات والدعم" />
          <Card>
            <SettingsRow
              tone="blue"
              icon={<Bell size={18} color={tones.blue.fg} />}
              label="الإشعارات"
              hint="إدارة تنبيهات التطبيق"
            />
            <SettingsRow
              tone="purple"
              icon={<KeyRound size={18} color={tones.purple.fg} />}
              label="تغيير كلمة المرور"
              hint="تحديث بيانات تسجيل الدخول"
            />
            <SettingsRow
              tone="orange"
              icon={<MailQuestion size={18} color={tones.orange.fg} />}
              label="تواصل مع الإدارة"
              hint="إرسال استفسار عبر البريد"
              onPress={() => Linking.openURL('mailto:admin@example.ly')}
            />
            <SettingsRow
              tone="sky"
              icon={<Shield size={18} color={tones.sky.fg} />}
              label="سياسة الخصوصية"
              hint="اطلع على شروط الاستخدام"
              isLast
            />
          </Card>

          <View className="h-6" />
          <Button
            label="تسجيل الخروج"
            variant="danger"
            iconLeft={<LogOut size={18} color="#fff" />}
            onPress={() => {
              haptic.warning();
              logout();
            }}
          />

          <Text className="font-kufi text-muted text-[11px] text-center mt-6">
            بوابة الطالب · الإصدار 1.0.0
          </Text>
        </>
      )}
    </Screen>
  );
}
