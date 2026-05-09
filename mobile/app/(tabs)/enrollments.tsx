import { useRouter } from 'expo-router';
import { BookOpen } from 'lucide-react-native';
import { useState } from 'react';
import { ScrollView, Text, View } from 'react-native';

import { useEnrollments, useSemesters } from '@/api/queries';
import {
  Card,
  EmptyState,
  Pill,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';

function SemesterChip({
  label,
  active,
  onPress,
}: {
  label: string;
  active: boolean;
  onPress: () => void;
}) {
  return (
    <Text
      onPress={onPress}
      className={`font-kufi-medium text-[13px] py-2 px-4 rounded-full me-2 border ${
        active ? 'bg-primary text-white border-primary' : 'bg-surface text-ink border-border'
      }`}
    >
      {label}
    </Text>
  );
}

export default function EnrollmentsScreen() {
  const router = useRouter();
  const { data: semesters } = useSemesters();
  const [selected, setSelected] = useState<number | null>(null);

  const activeSemesterId = (semesters ?? []).find((s) => s.is_active)?.id;
  const semesterId = selected ?? activeSemesterId ?? null;

  const { data: enrollments, isLoading } = useEnrollments(semesterId ?? undefined);

  return (
    <Screen title="مقرراتي">
      {!!semesters && semesters.length > 0 && (
        <View className="mb-3">
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingVertical: 4 }}>
            {semesters.map((s) => (
              <SemesterChip
                key={s.id}
                label={s.name}
                active={s.id === semesterId}
                onPress={() => setSelected(s.id)}
              />
            ))}
          </ScrollView>
        </View>
      )}

      {isLoading ? (
        <>
          <SkeletonCard />
          <SkeletonCard />
        </>
      ) : (enrollments ?? []).length === 0 ? (
        <EmptyState
          icon={<BookOpen size={28} color="#64748B" />}
          title="لم يتم تسجيلك في أي مقرر لهذا الفصل"
          body="يمكنك استعراض شجرة المقررات لمعرفة المتاح في تخصصك."
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
              <View className="flex-row-reverse items-start justify-between">
                <View className="flex-1">
                  <Text className="font-kufi-bold text-ink text-[16px] text-right">{c?.name}</Text>
                  <Text className="font-kufi text-muted text-[13px] mt-0.5 text-right">{c?.code}</Text>
                  {!!facultyName && (
                    <Text className="font-kufi text-muted text-[12px] mt-1 text-right">المحاضر: {facultyName}</Text>
                  )}
                  <View className="mt-2 flex-row gap-2">
                    <Pill label={isElective ? 'اختياري' : 'إجباري'} tone={isElective ? 'info' : 'primary'} />
                    {!!c?.credits && <Pill label={`${c.credits} ساعات`} tone="neutral" />}
                  </View>
                </View>
                <View className="ms-2">
                  <StatusBadge status={e.status} />
                </View>
              </View>
            </Card>
          );
        })
      )}
    </Screen>
  );
}
