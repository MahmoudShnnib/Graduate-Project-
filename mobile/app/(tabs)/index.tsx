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
import { useState } from 'react';
import { Text, View } from 'react-native';

import {
  useActiveSemester,
  useEnrollments,
  useMe,
  useNotifications,
} from '@/api/queries';
import {
  Card,
  ErrorState,
  IconTile,
  Pill,
  QuickActionTile,
  SectionHeader,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors, tones } from '@/theme/tokens';

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
      <Text
        className="font-kufi-bold text-primary text-[24px]"
        style={{ fontVariant: ['tabular-nums'] }}
      >
        {value}
      </Text>
      <Text className="font-kufi text-muted text-[11px] mt-0.5">{label}</Text>
    </View>
  );
}

export default function HomeScreen() {
  const router = useRouter();
  const { data: student, isLoading: meLoading, isError: meError, refetch: refetchMe } = useMe();
  const { data: semester, refetch: refetchSemester } = useActiveSemester();
  const { data: enrollments, refetch: refetchEnrollments } = useEnrollments();
  const { data: notifications, refetch: refetchNotifications } = useNotifications();
  const [refreshing, setRefreshing] = useState(false);

  async function onRefresh() {
    setRefreshing(true);
    try {
      await Promise.all([
        refetchMe(),
        refetchSemester(),
        refetchEnrollments(),
        refetchNotifications(),
      ]);
    } finally {
      setRefreshing(false);
    }
  }

  const enrolledCount = (enrollments ?? []).filter((e) => e.status.value === 'enrolled').length;
  const unreadCount = (notifications ?? []).filter((n) => !n.is_read).length;

  const greeting = student
    ? `مرحباً، ${student.name?.split(' ').slice(0, 2).join(' ')}`
    : 'الرئيسية';
  const subtitle = student ? `رقم القيد: ${student.enrollment_no}` : undefined;

  return (
    <Screen
      title={greeting}
      subtitle={subtitle}
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
      onRefresh={onRefresh}
      refreshing={refreshing}
    >
      {meError ? (
        <ErrorState onRetry={() => refetchMe()} />
      ) : meLoading || !student ? (
        <SkeletonCard />
      ) : (
        <>
          {/* Overlapping stats card */}
          {!!semester && (
            <Card elevated>
              <View className="flex-row items-center justify-between mb-3">
                <Text className="font-kufi-bold text-ink text-[15px] text-right flex-1">{semester.name}</Text>
                <StatusBadge status={student.status} withDot />
              </View>
              <View className="flex-row border-t border-border pt-3">
                <MiniStat value={enrolledCount} label="مقررات مسجلة" />
                <View className="w-px bg-border" />
                <MiniStat value={unreadCount} label="إشعارات جديدة" />
                <View className="w-px bg-border" />
                <MiniStat value={Number(student.gpa).toFixed(2)} label="المعدل التراكمي" />
              </View>
              {(semester.survey_open || semester.add_drop_open) && (
                <View className="flex-row gap-2 flex-wrap mt-3">
                  {semester.survey_open && <Pill label="نافذة الاستطلاع مفتوحة" tone="success" />}
                  {semester.add_drop_open && <Pill label="نافذة الإضافة/الحذف مفتوحة" tone="info" />}
                </View>
              )}
            </Card>
          )}

          {/* Warnings */}
          {!!student.active_warnings && student.active_warnings.length > 0 && (
            <>
              <View className="h-5" />
              <SectionHeader title="التحذيرات النشطة" />
              {student.active_warnings.slice(0, 2).map((w) => (
                <Card key={w.id} className="mb-2 flex-row items-start" >
                  <IconTile tone="amber" size="md" rounded="circle">
                    <AlertTriangle size={20} color={tones.amber.fg} />
                  </IconTile>
                  <View className="flex-1 ms-3">
                    <Text className="font-kufi-bold text-ink text-[13px] text-right">{w.type.label}</Text>
                    {!!w.course && (
                      <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">
                        {w.course.name} · {w.course.code}
                      </Text>
                    )}
                  </View>
                </Card>
              ))}
            </>
          )}

          {/* Quick actions */}
          <View className="h-5" />
          <SectionHeader title="إجراءات سريعة" />
          <View className="flex-row gap-3 mb-3">
            <QuickActionTile
              tone="blue"
              label="استطلاع المقررات"
              icon={<ClipboardList size={24} color={tones.blue.fg} />}
              onPress={() => router.push('/(tabs)/requests/survey')}
            />
            <QuickActionTile
              tone="purple"
              label="طلب إضافة/حذف"
              icon={<FileEdit size={24} color={tones.purple.fg} />}
              onPress={() => router.push('/(tabs)/requests/add-drop')}
            />
          </View>
          <View className="flex-row gap-3">
            <QuickActionTile
              tone="green"
              label="درجاتي"
              icon={<GraduationCap size={24} color={tones.green.fg} />}
              onPress={() => router.push('/(tabs)/grades')}
            />
            <QuickActionTile
              tone="orange"
              label="تسجيل الرسالة"
              icon={<ScrollText size={24} color={tones.orange.fg} />}
              onPress={() => router.push('/(tabs)/requests/thesis')}
            />
          </View>

          {/* Notifications */}
          <View className="h-5" />
          <SectionHeader title="آخر الإشعارات" />
          {(notifications ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد إشعارات</Text>
            </Card>
          ) : (
            (notifications ?? []).slice(0, 3).map((n) => (
              <Card key={n.id} className="mb-2 flex-row items-center">
                <IconTile tone={n.type === 'warning' ? 'amber' : 'sky'} size="md" rounded="circle">
                  {n.type === 'warning' ? (
                    <AlertTriangle size={18} color={tones.amber.fg} />
                  ) : (
                    <BookOpen size={18} color={tones.sky.fg} />
                  )}
                </IconTile>
                <View className="flex-1 ms-3">
                  <Text className="font-kufi-bold text-ink text-[13px] text-right">{n.title}</Text>
                  <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">{relativeArabic(n.created_at)}</Text>
                </View>
                <ChevronLeft size={18} color={colors.muted} />
              </Card>
            ))
          )}
        </>
      )}
    </Screen>
  );
}
