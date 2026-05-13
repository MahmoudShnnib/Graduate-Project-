import { useRouter } from 'expo-router';
import { CalendarCheck, Plus, Trash2 } from 'lucide-react-native';
import { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';

import {
  useActiveSemester,
  useAvailableSurveyCourses,
  useMe,
  useSubmitSurvey,
  useSurveys,
} from '@/api/queries';
import type { Course } from '@/api/types';
import {
  Banner,
  Button,
  Card,
  EmptyState,
  IconTile,
  SectionHeader,
  SkeletonCard,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { haptic } from '@/lib/haptics';
import { colors, tones } from '@/theme/tokens';

export default function SurveyScreen() {
  const router = useRouter();
  const { data: me } = useMe();
  const { data: semester, isLoading: semLoading } = useActiveSemester();
  const { data: existing } = useSurveys();
  const { data: available, isLoading: avLoading } = useAvailableSurveyCourses();
  const submit = useSubmitSurvey();

  const [selected, setSelected] = useState<Course[]>([]);
  const [feedback, setFeedback] = useState<{ tone: 'success' | 'danger'; msg: string } | null>(null);

  useEffect(() => {
    if (existing && existing.length > 0 && selected.length === 0) {
      const courses = existing.map((s) => s.course).filter((c): c is Course => !!c);
      setSelected(courses);
    }
  }, [existing, selected.length]);

  const availableFiltered = useMemo(() => {
    const ids = new Set(selected.map((c) => c.id));
    return (available ?? []).filter((c) => !ids.has(c.id));
  }, [available, selected]);

  const isActive = me?.status.value === 'active';
  const surveyOpen = !!semester?.survey_open;
  const canSubmit = isActive && surveyOpen && selected.length > 0;

  function add(c: Course) {
    setSelected((prev) => [...prev, c]);
  }
  function remove(id: number) {
    setSelected((prev) => prev.filter((c) => c.id !== id));
  }

  async function onSubmit() {
    setFeedback(null);
    try {
      await submit.mutateAsync(selected.map((c) => c.id));
      haptic.success();
      setFeedback({ tone: 'success', msg: 'تم حفظ استطلاعك بنجاح.' });
    } catch (err: any) {
      haptic.error();
      setFeedback({ tone: 'danger', msg: err?.response?.data?.message ?? 'تعذر الحفظ.' });
    }
  }

  if (semLoading) {
    return (
      <Screen
        title="استطلاع المقررات"
        headerVariant="hero"
        onBack={() => router.back()}
      >
        <SkeletonCard />
      </Screen>
    );
  }

  return (
    <Screen
      title="استطلاع المقررات"
      subtitle={semester?.name}
      headerVariant="hero"
      heroSize="md"
      overlap={28}
      onBack={() => router.back()}
    >
      {!isActive ? (
        <Banner kind="warning" title="حسابك غير نشط" body="يجب أن يكون حسابك نشطاً لتقديم الاستطلاع." />
      ) : !surveyOpen ? (
        <Banner kind="info" title="الاستطلاع مغلق حالياً" body="ترقّب فتح النافذة من قبل الإدارة." />
      ) : (
        <Banner
          kind="success"
          title="الاستطلاع مفتوح"
          body="أضف المقررات التي ترغب بتسجيلها وأعد ترتيبها بحسب الأولوية."
          icon={<CalendarCheck size={22} color={colors.success} />}
        />
      )}

      {!!feedback && (
        <View className="mt-3">
          <Banner kind={feedback.tone === 'success' ? 'success' : 'danger'} title={feedback.msg} />
        </View>
      )}

      <View className="h-4" />
      <SectionHeader title="المقررات المختارة" subtitle={`${selected.length} مقرر`} />
      {selected.length === 0 ? (
        <Card>
          <Text className="font-kufi text-muted text-[13px] text-center py-2">لم تختر مقررات بعد</Text>
        </Card>
      ) : (
        selected.map((c, i) => (
          <Card key={c.id} className="mb-2 flex-row-reverse items-center">
            <View className="w-7 h-7 rounded-full bg-primary items-center justify-center">
              <Text className="font-kufi-bold text-white text-[12px]">{i + 1}</Text>
            </View>
            <View className="flex-1 mx-3">
              <Text className="font-kufi-bold text-ink text-[14px] text-right">{c.name}</Text>
              <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{c.code}</Text>
            </View>
            <Pressable
              onPress={() => remove(c.id)}
              accessibilityLabel="إزالة المقرر"
              hitSlop={8}
            >
              <IconTile tone="red" size="md" rounded="circle">
                <Trash2 size={18} color={tones.red.fg} />
              </IconTile>
            </Pressable>
          </Card>
        ))
      )}

      <View className="h-4" />
      <SectionHeader title="المقررات المتاحة" subtitle={`${availableFiltered.length} مقرر`} />
      {avLoading ? (
        <SkeletonCard />
      ) : availableFiltered.length === 0 ? (
        <EmptyState title="لا توجد مقررات إضافية" body="جميع المقررات المتاحة مضافة إلى قائمتك." />
      ) : (
        availableFiltered.map((c) => (
          <Card key={c.id} className="mb-2 flex-row-reverse items-center">
            <View className="flex-1">
              <Text className="font-kufi-bold text-ink text-[14px] text-right">{c.name}</Text>
              <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{c.code}</Text>
            </View>
            <Pressable onPress={() => add(c)} accessibilityLabel="إضافة المقرر" hitSlop={8}>
              <IconTile tone="blue" size="md" rounded="circle">
                <Plus size={18} color={tones.blue.fg} />
              </IconTile>
            </Pressable>
          </Card>
        ))
      )}

      <View className="h-6" />
      <Button
        label="تقديم الاستطلاع"
        size="lg"
        disabled={!canSubmit}
        loading={submit.isPending}
        onPress={onSubmit}
      />
    </Screen>
  );
}
