import { Award, BookOpen, Layers } from 'lucide-react-native';
import { useState } from 'react';
import { ScrollView, Text, View } from 'react-native';
import Svg, { Circle, Defs, LinearGradient, Stop } from 'react-native-svg';

import { useEnrollments, useGrades, useMe, useSemesters } from '@/api/queries';
import type { Enrollment } from '@/api/types';
import {
  Card,
  EmptyState,
  ErrorState,
  IconTile,
  Pill,
  SemesterChip,
  SkeletonCard,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { ToneKey, colors, tones } from '@/theme/tokens';

type Tier = {
  label: string;
  tone: ToneKey;
  pillTone: 'success' | 'warning' | 'danger' | 'info' | 'neutral';
  stroke: string;
  trackTint: string;
};

function tierFor(gpa: number): Tier {
  if (gpa >= 3.5)
    return {
      label: 'ممتاز',
      tone: 'green',
      pillTone: 'success',
      stroke: colors.success,
      trackTint: tones.green.bg,
    };
  if (gpa >= 3.0)
    return {
      label: 'جيد جداً',
      tone: 'sky',
      pillTone: 'info',
      stroke: colors.info,
      trackTint: tones.sky.bg,
    };
  if (gpa >= 2.5)
    return {
      label: 'جيد',
      tone: 'blue',
      pillTone: 'info',
      stroke: colors.primary,
      trackTint: tones.blue.bg,
    };
  if (gpa >= 2.0)
    return {
      label: 'مقبول',
      tone: 'amber',
      pillTone: 'warning',
      stroke: colors.warning,
      trackTint: tones.amber.bg,
    };
  return {
    label: 'ضعيف',
    tone: 'red',
    pillTone: 'danger',
    stroke: colors.danger,
    trackTint: tones.red.bg,
  };
}

function GpaGauge({ gpa }: { gpa: number }) {
  const size = 172;
  const stroke = 16;
  const radius = (size - stroke) / 2;
  const circumference = 2 * Math.PI * radius;
  const ratio = Math.min(Math.max(gpa, 0), 4) / 4;
  const offset = circumference * (1 - ratio);
  const tier = tierFor(gpa);

  return (
    <View className="items-center justify-center" style={{ width: size, height: size }}>
      <Svg width={size} height={size}>
        <Defs>
          <LinearGradient id="gauge" x1="0" y1="0" x2="1" y2="1">
            <Stop offset="0" stopColor={tier.stroke} stopOpacity="1" />
            <Stop offset="1" stopColor={tier.stroke} stopOpacity="0.7" />
          </LinearGradient>
        </Defs>
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={tier.trackTint}
          strokeWidth={stroke}
          fill="none"
        />
        <Circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke="url(#gauge)"
          strokeWidth={stroke}
          fill="none"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          transform={`rotate(-90 ${size / 2} ${size / 2})`}
        />
      </Svg>
      <View className="absolute items-center">
        <Text className="font-kufi text-muted text-[11px] mb-1">المعدل التراكمي</Text>
        <Text
          className="font-kufi-bold text-ink"
          style={{ fontSize: 44, lineHeight: 50, fontVariant: ['tabular-nums'] }}
        >
          {gpa.toFixed(2)}
        </Text>
        <Text className="font-kufi text-muted text-[10px] mt-0.5" style={{ fontVariant: ['tabular-nums'] }}>
          من 4.00
        </Text>
      </View>
    </View>
  );
}

function StatBlock({
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
    <View className="flex-1 items-center py-2">
      <IconTile tone={tone} size="md" rounded="circle">
        {icon}
      </IconTile>
      <Text
        className="font-kufi-bold text-ink text-[18px] mt-2"
        style={{ fontVariant: ['tabular-nums'] }}
      >
        {value}
      </Text>
      <Text className="font-kufi text-muted text-[10px] mt-0.5 text-center">{label}</Text>
    </View>
  );
}

function GradePill({ enrollment }: { enrollment: Enrollment }) {
  const g = enrollment.grade;
  if (!g || g.publish_state === 'unpublished') {
    return <Pill label="لم تُنشر" tone="neutral" />;
  }
  if (g.publish_state === 'midterm_only') {
    return <Pill label="أعمال السنة" tone="warning" />;
  }
  const points = g.points ?? 0;
  const tone = g.letter === 'F' ? 'danger' : points >= 3 ? 'success' : points >= 2 ? 'warning' : 'danger';
  return <Pill label={`${g.letter ?? '—'}  ·  ${g.total ?? '—'}`} tone={tone} size="md" />;
}

function ScoreRow({
  label,
  value,
  max,
  tone,
}: {
  label: string;
  value: number | null | undefined;
  max: number;
  tone: 'sky' | 'green';
}) {
  const pct = value != null && max > 0 ? Math.min(100, Math.round((value / max) * 100)) : 0;
  return (
    <View className="mt-2.5">
      <View className="flex-row-reverse items-center justify-between mb-1">
        <Text className="font-kufi text-muted text-[12px]">{label}</Text>
        <Text
          className="font-kufi-medium text-ink text-[12px]"
          style={{ fontVariant: ['tabular-nums'] }}
        >
          {value ?? '—'} / {max}
        </Text>
      </View>
      <View className="h-1.5 bg-bg rounded-full overflow-hidden">
        <View
          style={{
            height: '100%',
            width: `${pct}%`,
            backgroundColor: tones[tone].fg,
            borderRadius: 999,
          }}
        />
      </View>
    </View>
  );
}

export default function GradesScreen() {
  const { data: me, refetch: refetchMe } = useMe();
  const { data: semesters, refetch: refetchSemesters } = useSemesters();
  const { data: allEnrollments, refetch: refetchAllEnrollments } = useEnrollments();
  const [selected, setSelected] = useState<number | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const activeSemesterId = (semesters ?? []).find((s) => s.is_active)?.id;
  const semesterId = selected ?? activeSemesterId ?? null;

  const { data: rows, isLoading, isError, refetch } = useGrades(semesterId ?? undefined);

  async function onRefresh() {
    setRefreshing(true);
    try {
      await Promise.all([
        refetchMe(),
        refetchSemesters(),
        refetchAllEnrollments(),
        refetch(),
      ]);
    } finally {
      setRefreshing(false);
    }
  }

  const gpa = me ? Number(me.gpa) : 0;
  const tier = tierFor(gpa);

  // Aggregate stats from all enrollments (passed = published + letter !== F)
  const passed = (allEnrollments ?? []).filter(
    (e) => e.grade?.publish_state === 'published' && e.grade?.letter !== 'F',
  );
  const passedCount = passed.length;
  const earnedCredits = passed.reduce((sum, e) => sum + (e.course_offering.course?.credits ?? 0), 0);

  return (
    <Screen
      title="الدرجات"
      subtitle="معدلك التراكمي ودرجاتك التفصيلية"
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
      onRefresh={onRefresh}
      refreshing={refreshing}
    >
      {/* GPA showpiece */}
      <Card elevated>
        <View className="items-center pt-3 pb-2">
          <GpaGauge gpa={gpa} />
          <View className="mt-3">
            <Pill label={tier.label} tone={tier.pillTone} size="md" />
          </View>
        </View>
        <View className="border-t border-border mt-4 pt-2 flex-row">
          <StatBlock
            tone={tier.tone}
            icon={<Award size={16} color={tones[tier.tone].fg} />}
            value={tier.label}
            label="التصنيف"
          />
          <View className="w-px bg-border" />
          <StatBlock
            tone="green"
            icon={<BookOpen size={16} color={tones.green.fg} />}
            value={passedCount}
            label="مقررات مجتازة"
          />
          <View className="w-px bg-border" />
          <StatBlock
            tone="blue"
            icon={<Layers size={16} color={tones.blue.fg} />}
            value={earnedCredits}
            label="ساعة معتمدة"
          />
        </View>
      </Card>

      {/* Semester selector */}
      <View className="h-5" />
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

      {isError ? (
        <ErrorState onRetry={() => refetch()} />
      ) : isLoading ? (
        <>
          <SkeletonCard />
          <SkeletonCard />
        </>
      ) : (rows ?? []).length === 0 ? (
        <EmptyState
          icon={<BookOpen size={28} color={colors.muted} />}
          title="لا توجد درجات لهذا الفصل"
          body="ستظهر درجاتك بعد أن تنشرها الإدارة."
        />
      ) : (
        (rows ?? []).map((e) => {
          const g = e.grade;
          const c = e.course_offering.course;
          const co = e.course_offering;
          return (
            <Card key={e.id} className="mb-3">
              <View className="flex-row-reverse items-start">
                <View className="flex-1">
                  <Text className="font-kufi-bold text-ink text-[15px] text-right">{c?.name}</Text>
                  <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">
                    {c?.code}
                    {!!c?.credits && ` · ${c.credits} ساعات`}
                  </Text>
                </View>
                <View className="ms-2">
                  <GradePill enrollment={e} />
                </View>
              </View>

              {g && (g.publish_state === 'midterm_only' || g.publish_state === 'published') && (
                <View className="mt-2">
                  <ScoreRow
                    label="أعمال السنة"
                    value={g.coursework_grade}
                    max={co.coursework_max}
                    tone="sky"
                  />
                  {g.publish_state === 'published' && (
                    <ScoreRow
                      label="الامتحان النهائي"
                      value={g.final_grade}
                      max={co.final_max}
                      tone="green"
                    />
                  )}
                </View>
              )}

              {g?.publish_state === 'midterm_only' && (
                <View className="mt-3 bg-amber-50 rounded-xl px-3 py-2 flex-row-reverse items-center">
                  <Text className="font-kufi text-warning text-[12px]">نُشرت أعمال السنة فقط</Text>
                </View>
              )}
              {(!g || g.publish_state === 'unpublished') && e.status.value === 'enrolled' && (
                <View className="mt-3 bg-bg rounded-xl px-3 py-2 flex-row-reverse items-center">
                  <Text className="font-kufi text-muted text-[12px]">لم تُنشر الدرجة بعد</Text>
                </View>
              )}
            </Card>
          );
        })
      )}
    </Screen>
  );
}
