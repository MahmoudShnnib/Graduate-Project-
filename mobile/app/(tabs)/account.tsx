import { Bell, ChevronLeft, KeyRound, LogOut, MailQuestion } from 'lucide-react-native';
import { Linking, Text, View } from 'react-native';

import { useMe } from '@/api/queries';
import { Avatar, Button, Card, Pill, SectionHeader, SkeletonCard, StatusBadge } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/hooks/useAuth';
import { colors } from '@/theme/tokens';

function InfoChip({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <View className="flex-1 bg-bg border border-border rounded-xl p-3">
      <Text className="font-kufi text-muted text-[11px] text-right">{label}</Text>
      <Text className="font-kufi-bold text-ink text-[14px] mt-1 text-right" numberOfLines={1}>
        {value ?? '—'}
      </Text>
    </View>
  );
}

function SettingsRow({
  icon,
  label,
  onPress,
  danger,
  isLast,
}: {
  icon: React.ReactNode;
  label: string;
  onPress?: () => void;
  danger?: boolean;
  isLast?: boolean;
}) {
  return (
    <View
      className={`flex-row items-center justify-between py-3.5 ${isLast ? '' : 'border-b border-border'}`}
      onTouchEnd={onPress}
    >
      <View className="flex-row items-center gap-3 flex-1">
        {icon}
        <Text className={`font-kufi-medium text-[14px] ${danger ? 'text-danger' : 'text-ink'}`}>{label}</Text>
      </View>
      <ChevronLeft size={20} color={colors.muted} />
    </View>
  );
}

export default function AccountScreen() {
  const { logout } = useAuth();
  const { data: student, isLoading } = useMe();

  return (
    <Screen title="حسابي">
      {isLoading || !student ? (
        <SkeletonCard />
      ) : (
        <>
          <Card>
            <View className="items-center pt-2">
              <Avatar name={student.name} size={72} />
              <Text className="font-kufi-bold text-ink text-[20px] mt-3 text-center">{student.name}</Text>
              {!!student.specialization && (
                <Text className="font-kufi text-muted text-[14px] mt-1 text-center">{student.specialization.name}</Text>
              )}
              <Text className="font-kufi text-muted text-[13px] mt-1">رقم القيد: {student.enrollment_no}</Text>
              <View className="mt-3">
                <StatusBadge status={student.status} withDot />
              </View>
            </View>
          </Card>

          <View className="h-5" />
          <SectionHeader title="معلومات الحساب" />
          <View className="flex-row gap-3">
            <InfoChip label="سنة القبول" value={student.admission_year ?? '—'} />
            <InfoChip label="نوع التسجيل" value={student.is_transfer ? 'منتقل' : 'مستجد'} />
            <InfoChip label="المعدل" value={Number(student.gpa).toFixed(2)} />
          </View>

          <View className="h-3" />
          <View className="flex-row gap-3">
            <InfoChip label="البريد" value={student.email} />
            <InfoChip label="الهاتف" value={student.phone} />
          </View>

          {student.warnings_count > 0 && (
            <>
              <View className="h-5" />
              <SectionHeader title="التحذيرات" />
              <Card>
                <View className="flex-row items-center justify-between">
                  <Text className="font-kufi-medium text-ink text-[14px] text-right flex-1">
                    لديك {student.warnings_count} تحذير نشط
                  </Text>
                  <Pill label={String(student.warnings_count)} tone="warning" />
                </View>
              </Card>
            </>
          )}

          <View className="h-6" />
          <SectionHeader title="الإعدادات" />
          <Card>
            <SettingsRow icon={<Bell size={20} color={colors.ink} />} label="الإشعارات" />
            <SettingsRow icon={<KeyRound size={20} color={colors.ink} />} label="تغيير كلمة المرور" />
            <SettingsRow
              icon={<MailQuestion size={20} color={colors.ink} />}
              label="تواصل مع الإدارة"
              isLast
              onPress={() => Linking.openURL('mailto:admin@example.ly')}
            />
          </Card>

          <View className="h-6" />
          <Button label="تسجيل الخروج" variant="danger" iconLeft={<LogOut size={18} color="#fff" />} onPress={logout} />
        </>
      )}
    </Screen>
  );
}
