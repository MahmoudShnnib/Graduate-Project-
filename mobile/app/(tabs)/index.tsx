import { useRouter } from 'expo-router';
import {
  AlertTriangle,
  BookOpen,
  ChevronLeft,
  ClipboardList,
  FileEdit,
  GraduationCap,
  ScrollText,
} from 'lucide-react-native';
import { Pressable, Text, View } from 'react-native';

import {
  useActiveSemester,
  useEnrollments,
  useMe,
  useNotifications,
} from '@/api/queries';
import {
  Card,
  ErrorState,
  Pill,
  SectionHeader,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

function relativeArabic(iso?: string | null): string {
  if (!iso) return '';
  const diffMin = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
  if (diffMin < 1) return 'الآن';
  if (diffMin < 60) return `قبل ${diffMin} دقيقة`;
  const h = Math.floor(diffMin / 60);
  if (h < 24) return `قبل ${h} ساعة`;
  const d = Math.floor(h / 24);
  if (d < 7) return `قبل ${d} يوم`;
  return new Date(iso).toLocaleDateString('ar');
}

function MiniStat({ value, label }: { value: string | number; label: string }) {
  return (
    <View className="flex-1 items-center">
      <Text className="font-kufi-bold text-ink text-[22px]">{value}</Text>
      <Text className="font-kufi text-muted text-[12px] mt-0.5">{label}</Text>
    </View>
  );
}

function QuickAction({
  icon,
  label,
  onPress,
}: {
  icon: React.ReactNode;
  label: string;
  onPress: () => void;
}) {
  return (
    <Pressable
      onPress={onPress}
      className="flex-1 bg-surface border border-border rounded-2xl items-center justify-center py-5 active:bg-bg"
      style={{ minHeight: 96 }}
    >
      <View className="w-11 h-11 rounded-full bg-blue-50 items-center justify-center mb-2">{icon}</View>
      <Text className="font-kufi-medium text-ink text-[13px] text-center">{label}</Text>
    </Pressable>
  );
}

export default function HomeScreen() {
  const router = useRouter();
  const { data: student, isLoading: meLoading, isError: meError, refetch: refetchMe } = useMe();
  const { data: semester } = useActiveSemester();
  const { data: enrollments } = useEnrollments();
  const { data: notifications } = useNotifications();

  const enrolledCount = (enrollments ?? []).filter((e) => e.status.value === 'enrolled').length;
  const unreadCount = (notifications ?? []).filter((n) => !n.is_read).length;

  return (
    <Screen
      title={student ? `مرحباً، ${student.name?.split(' ').slice(0, 2).join(' ')}` : 'الرئيسية'}
      subtitle={student ? `رقم القيد: ${student.enrollment_no}` : undefined}
    >
      {meError ? (
        <ErrorState onRetry={() => refetchMe()} />
      ) : meLoading || !student ? (
        <SkeletonCard />
      ) : (
        <>
          <View className="mb-3">
            <StatusBadge status={student.status} withDot />
          </View>

          {!!semester && (
            <Card>
              <Text className="font-kufi-bold text-ink text-[15px] text-right">{semester.name}</Text>
              <View className="h-3" />
              <View className="flex-row">
                <MiniStat value={enrolledCount} label="مقررات مسجلة" />
                <View className="w-px bg-border mx-2" />
                <MiniStat value={unreadCount} label="إشعارات جديدة" />
                <View className="w-px bg-border mx-2" />
                <MiniStat value={Number(student.gpa).toFixed(2)} label="المعدل التراكمي" />
              </View>
              <View className="h-3" />
              <View className="flex-row gap-2 flex-wrap">
                {semester.survey_open && <Pill label="نافذة الاستطلاع مفتوحة" tone="success" />}
                {semester.add_drop_open && <Pill label="نافذة الإضافة/الحذف مفتوحة" tone="info" />}
              </View>
            </Card>
          )}

          {!!student.active_warnings && student.active_warnings.length > 0 && (
            <>
              <View className="h-5" />
              <SectionHeader title="التحذيرات النشطة" />
              {student.active_warnings.slice(0, 2).map((w) => (
                <View key={w.id} className="bg-amber-50 border-r-4 border-warning rounded-xl p-3.5 mb-2 flex-row">
                  <AlertTriangle size={20} color={colors.warning} />
                  <View className="flex-1 ms-3">
                    <Text className="font-kufi-bold text-warning text-[13px] text-right">{w.type.label}</Text>
                    {!!w.course && (
                      <Text className="font-kufi text-warning text-[12px] mt-0.5 text-right opacity-90">
                        {w.course.name} · {w.course.code}
                      </Text>
                    )}
                  </View>
                </View>
              ))}
            </>
          )}

          <View className="h-5" />
          <SectionHeader title="إجراءات سريعة" />
          <View className="flex-row gap-3 mb-3">
            <QuickAction
              icon={<ClipboardList size={22} color={colors.primary} />}
              label="استطلاع المقررات"
              onPress={() => router.push('/(tabs)/requests/survey')}
            />
            <QuickAction
              icon={<FileEdit size={22} color={colors.primary} />}
              label="طلب إضافة/حذف"
              onPress={() => router.push('/(tabs)/requests/add-drop')}
            />
          </View>
          <View className="flex-row gap-3">
            <QuickAction
              icon={<GraduationCap size={22} color={colors.primary} />}
              label="درجاتي"
              onPress={() => router.push('/(tabs)/grades')}
            />
            <QuickAction
              icon={<ScrollText size={22} color={colors.primary} />}
              label="تسجيل الرسالة"
              onPress={() => router.push('/(tabs)/requests/thesis')}
            />
          </View>

          <View className="h-5" />
          <SectionHeader title="آخر الإشعارات" />
          {(notifications ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد إشعارات</Text>
            </Card>
          ) : (
            (notifications ?? []).slice(0, 3).map((n) => (
              <View key={n.id} className="bg-surface border border-border rounded-2xl p-3.5 mb-2 flex-row items-center">
                <View
                  className={`w-9 h-9 rounded-full items-center justify-center ${
                    n.type === 'warning' ? 'bg-amber-50' : 'bg-blue-50'
                  }`}
                >
                  {n.type === 'warning' ? (
                    <AlertTriangle size={18} color={colors.warning} />
                  ) : (
                    <BookOpen size={18} color={colors.primary} />
                  )}
                </View>
                <View className="flex-1 ms-3">
                  <Text className="font-kufi-bold text-ink text-[13px] text-right">{n.title}</Text>
                  <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">{relativeArabic(n.created_at)}</Text>
                </View>
                <ChevronLeft size={18} color={colors.muted} />
              </View>
            ))
          )}
        </>
      )}
    </Screen>
  );
}
