import { useRouter } from 'expo-router';
import { BookOpen, Clock, GraduationCap, Layers } from 'lucide-react-native';
import { useState } from 'react';
import { ScrollView, Text, View } from 'react-native';

import { useEnrollments, useSemesters } from '@/api/queries';
import {
  Card,
  EmptyState,
  ErrorState,
  IconTile,
  Pill,
  SemesterChip,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { ToneKey, colors, tones } from '@/theme/tokens';

function MiniStat({
  tone,
  icon,
  value,
  label,
}: {
  tone: ToneKey;
  icon: React.ReactNode;
  value: string | number;
  label: string;
}) {
  return (
    <View className="flex-1 items-center py-1">
      <IconTile tone={tone} size="md" rounded="circle">
        {icon}
      </IconTile>
      <Text
        className="font-kufi-bold text-ink text-[20px] mt-2"
        style={{ fontVariant: ['tabular-nums'] }}
      >
        {value}
      </Text>
      <Text className="font-kufi text-muted text-[10px] mt-0.5 text-center">{label}</Text>
    </View>
  );
}

export default function EnrollmentsScreen() {
  const router = useRouter();
  const { data: semesters, refetch: refetchSemesters } = useSemesters();
  const [selected, setSelected] = useState<number | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const activeSemesterId = (semesters ?? []).find((s) => s.is_active)?.id;
  const semesterId = selected ?? activeSemesterId ?? null;
  const selectedSemester = (semesters ?? []).find((s) => s.id === semesterId);

  const { data: enrollments, isLoading, isError, refetch } = useEnrollments(semesterId ?? undefined);

  async function onRefresh() {
    setRefreshing(true);
    try {
      await Promise.all([refetchSemesters(), refetch()]);
    } finally {
      setRefreshing(false);
    }
  }

  const enrolled = (enrollments ?? []).filter((e) => e.status.value === 'enrolled');
  const dropped = (enrollments ?? []).filter((e) => e.status.value === 'dropped' || e.status.value === 'withdrawn');
  const totalCredits = enrolled.reduce((sum, e) => sum + (e.course_offering.course?.credits ?? 0), 0);

  return (
    <Screen
      title="مقرراتي"
      subtitle={selectedSemester?.name ?? 'المقررات المسجلة'}
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
      onRefresh={onRefresh}
      refreshing={refreshing}
    >
      {/* Summary card overlapping the hero */}
      <Card elevated>
        <View className="flex-row">
          <MiniStat
            tone="blue"
            icon={<BookOpen size={16} color={tones.blue.fg} />}
            value={enrolled.length}
            label="مقررات مسجلة"
          />
          <View className="w-px bg-border" />
          <MiniStat
            tone="green"
            icon={<Layers size={16} color={tones.green.fg} />}
            value={totalCredits}
            label="ساعة دراسية"
          />
          <View className="w-px bg-border" />
          <MiniStat
            tone="red"
            icon={<Clock size={16} color={tones.red.fg} />}
            value={dropped.length}
            label="مسقطة/منسحبة"
          />
        </View>
      </Card>

      {/* Semester chips */}
      <View className="h-5" />
      {!!semesters && semesters.length > 0 && (
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{ paddingVertical: 4 }}
        >
          {semesters.map((s) => (
            <SemesterChip
              key={s.id}
              label={s.name}
              active={s.id === semesterId}
              onPress={() => setSelected(s.id)}
            />
          ))}
        </ScrollView>
      )}

      <View className="h-3" />

      {isError ? (
        <ErrorState onRetry={() => refetch()} />
      ) : isLoading ? (
        <>
          <SkeletonCard />
          <SkeletonCard />
        </>
      ) : (enrollments ?? []).length === 0 ? (
        <EmptyState
          icon={<BookOpen size={32} color={colors.muted} />}
          title="لم يتم تسجيلك في أي مقرر لهذا الفصل"
          body="استعرض شجرة المقررات لمعرفة المتاح في تخصصك ثم تواصل مع الإدارة للتسجيل."
          actionLabel="استعراض شجرة المقررات"
          onAction={() => router.push('/(tabs)/requests/course-tree')}
        />
      ) : (
        (enrollments ?? []).map((e) => {
          const c = e.course_offering.course;
          const facultyName = e.course_offering.faculty?.name;
          const isElective = c?.type === 'elective';
          return (
            <Card key={e.id} className="mb-3">
              <View className="flex-row-reverse items-start">
                <IconTile tone={isElective ? 'sky' : 'blue'} size="md">
                  <GraduationCap size={18} color={isElective ? tones.sky.fg : tones.blue.fg} />
                </IconTile>
                <View className="flex-1 mx-3">
                  <View className="flex-row-reverse items-start justify-between">
                    <View className="flex-1 pe-2">
                      <Text className="font-kufi-bold text-ink text-[15px] text-right" numberOfLines={2}>
                        {c?.name}
                      </Text>
                      <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">
                        {c?.code}
                      </Text>
                    </View>
                    <StatusBadge status={e.status} />
                  </View>

                  {!!facultyName && (
                    <Text className="font-kufi text-muted text-[12px] mt-2 text-right">
                      المحاضر: {facultyName}
                    </Text>
                  )}

                  <View className="mt-3 flex-row gap-2 flex-wrap">
                    <Pill
                      label={isElective ? 'اختياري' : 'إجباري'}
                      tone={isElective ? 'info' : 'primary'}
                    />
                    {!!c?.credits && <Pill label={`${c.credits} ساعات`} tone="neutral" />}
                  </View>
                </View>
              </View>
            </Card>
          );
        })
      )}
    </Screen>
  );
}
