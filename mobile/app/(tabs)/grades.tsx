import { useState } from 'react';
import { ScrollView, Text, View } from 'react-native';
import Svg, { Circle } from 'react-native-svg';

import { useGrades, useMe, useSemesters } from '@/api/queries';
import type { Enrollment } from '@/api/types';
import { Card, Pill, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

function GpaGauge({ gpa }: { gpa: number }) {
  const size = 140;
  const stroke = 10;
  const radius = (size - stroke) / 2;
  const circumference = 2 * Math.PI * radius;
  const ratio = Math.min(Math.max(gpa, 0), 4) / 4;
  const offset = circumference * (1 - ratio);
  const color = gpa >= 3 ? colors.success : gpa >= 2 ? colors.warning : colors.danger;

  return (
    <View className="items-center justify-center" style={{ width: size, height: size }}>
      <Svg width={size} height={size}>
        <Circle cx={size / 2} cy={size / 2} r={radius} stroke={colors.border} strokeWidth={stroke} fill="none" />
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={color}
          strokeWidth={stroke}
          fill="none"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          transform={`rotate(-90 ${size / 2} ${size / 2})`}
        />
      </Svg>
      <View className="absolute items-center">
        <Text className="font-kufi text-muted text-[12px]">المعدل</Text>
        <Text className="font-kufi-bold text-ink text-[32px]">{gpa.toFixed(2)}</Text>
        <Text className="font-kufi text-muted text-[10px]">من 4.00</Text>
      </View>
    </View>
  );
}

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

function GradePill({ enrollment }: { enrollment: Enrollment }) {
  const g = enrollment.grade;
  if (!g || g.publish_state === 'unpublished') {
    return <Pill label="لم تُنشر" tone="neutral" />;
  }
  if (g.publish_state === 'midterm_only') {
    return <Pill label="أعمال السنة فقط" tone="warning" />;
  }
  const tone = g.letter === 'F' ? 'danger' : (g.points ?? 0) >= 3 ? 'success' : (g.points ?? 0) >= 2 ? 'warning' : 'danger';
  return (
    <View className="items-center self-start">
      <Pill label={`${g.letter ?? '—'}  ·  ${g.total ?? '—'}`} tone={tone} />
    </View>
  );
}

export default function GradesScreen() {
  const { data: me } = useMe();
  const { data: semesters } = useSemesters();
  const [selected, setSelected] = useState<number | null>(null);

  const activeSemesterId = (semesters ?? []).find((s) => s.is_active)?.id;
  const semesterId = selected ?? activeSemesterId ?? null;

  const { data: rows, isLoading } = useGrades(semesterId ?? undefined);

  return (
    <Screen title="الدرجات">
      <Card>
        <View className="items-center">
          <GpaGauge gpa={me ? Number(me.gpa) : 0} />
        </View>
      </Card>

      <View className="h-4" />
      {!!semesters && semesters.length > 0 && (
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
      )}

      <View className="h-3" />

      {isLoading ? (
        <>
          <SkeletonCard />
          <SkeletonCard />
        </>
      ) : (
        (rows ?? []).map((e) => {
          const g = e.grade;
          const c = e.course_offering.course;
          return (
            <Card key={e.id} className="mb-3">
              <View className="flex-row-reverse items-start justify-between">
                <View className="flex-1">
                  <Text className="font-kufi-bold text-ink text-[15px] text-right">{c?.name}</Text>
                  <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{c?.code}</Text>

                  {g && (g.publish_state === 'midterm_only' || g.publish_state === 'published') && (
                    <View className="mt-3 gap-y-1.5">
                      <View className="flex-row-reverse items-center justify-between">
                        <Text className="font-kufi text-muted text-[12px]">أعمال السنة</Text>
                        <Text className="font-kufi-medium text-ink text-[13px]">
                          {g.coursework_grade ?? '—'} / {e.course_offering.coursework_max}
                        </Text>
                      </View>
                      <View className="flex-row-reverse items-center justify-between">
                        <Text className="font-kufi text-muted text-[12px]">الامتحان النهائي</Text>
                        <Text className="font-kufi-medium text-ink text-[13px]">
                          {g.publish_state === 'published' ? `${g.final_grade ?? '—'} / ${e.course_offering.final_max}` : '—'}
                        </Text>
                      </View>
                    </View>
                  )}

                  {g?.publish_state === 'midterm_only' && (
                    <Text className="font-kufi text-warning text-[12px] mt-2 text-right">
                      نُشرت أعمال السنة فقط
                    </Text>
                  )}
                  {(!g || g.publish_state === 'unpublished') && e.status.value === 'enrolled' && (
                    <Text className="font-kufi text-muted text-[12px] mt-2 text-right">لم تُنشر الدرجة بعد</Text>
                  )}
                </View>
                <View className="ms-2">
                  <GradePill enrollment={e} />
                </View>
              </View>
            </Card>
          );
        })
      )}
    </Screen>
  );
}
