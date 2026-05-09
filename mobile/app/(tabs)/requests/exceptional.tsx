import { Info } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';

import {
  useCreateExceptional,
  useExceptionalHistory,
  useExceptionalOptions,
} from '@/api/queries';
import { Banner, Button, Card, Input, Pill, SectionHeader, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

export default function ExceptionalScreen() {
  const { data: opts, isLoading } = useExceptionalOptions();
  const { data: history } = useExceptionalHistory();
  const create = useCreateExceptional();

  const [courseId, setCourseId] = useState<number | null>(null);
  const [oppType, setOppType] = useState<string | null>(null);
  const [reason, setReason] = useState('');
  const [feedback, setFeedback] = useState<{ tone: 'success' | 'danger'; msg: string } | null>(null);

  async function onSubmit() {
    if (!courseId || !oppType) return;
    setFeedback(null);
    try {
      await create.mutateAsync({ course_id: courseId, opportunity_type: oppType, reason });
      setFeedback({ tone: 'success', msg: 'تم تقديم الطلب' });
      setCourseId(null);
      setOppType(null);
      setReason('');
    } catch (err: any) {
      setFeedback({ tone: 'danger', msg: err?.response?.data?.message ?? 'تعذر تقديم الطلب' });
    }
  }

  return (
    <Screen title="الفرص الاستثنائية">
      <Banner
        kind="info"
        title="ما هي الفرصة الاستثنائية؟"
        body="تتيح لك التقدم لفرصة إضافية في مقررات رسبت فيها."
        icon={<Info size={22} color={colors.info} />}
      />

      <View className="h-4" />

      {isLoading ? (
        <SkeletonCard />
      ) : (
        <>
          <SectionHeader title="اختر المقرر" />
          {(opts?.failed_courses.data ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-3">لا توجد مقررات راسبة لتقديم فرصة عليها.</Text>
            </Card>
          ) : (
            (opts?.failed_courses.data ?? []).map((c) => (
              <Pressable
                key={c.id}
                onPress={() => setCourseId(c.id)}
                className={`bg-surface border rounded-xl p-3 mb-2 ${courseId === c.id ? 'border-primary bg-blue-50' : 'border-border'}`}
              >
                <Text className="font-kufi-bold text-ink text-[14px] text-right">{c.name}</Text>
                <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{c.code}</Text>
              </Pressable>
            ))
          )}

          <View className="h-3" />
          <SectionHeader title="نوع الفرصة" />
          <View className="flex-row gap-2">
            {(opts?.opportunity_types ?? []).map((t) => (
              <Pressable
                key={t.value}
                onPress={() => setOppType(t.value)}
                className={`flex-1 border rounded-xl py-3 items-center ${oppType === t.value ? 'border-primary bg-blue-50' : 'border-border bg-surface'}`}
              >
                <Text className={`font-kufi-medium text-[13px] ${oppType === t.value ? 'text-primary' : 'text-ink'}`}>
                  {t.label}
                </Text>
              </Pressable>
            ))}
          </View>

          <View className="h-3" />
          <Input
            label="مبررات الطلب"
            placeholder="اكتب مبرراتك (10 أحرف على الأقل)..."
            value={reason}
            onChangeText={setReason}
            multiline
            numberOfLines={4}
            style={{ height: 110, textAlignVertical: 'top' as any, paddingTop: 12 }}
          />

          {!!feedback && (
            <View className="mt-3">
              <Banner kind={feedback.tone === 'success' ? 'success' : 'danger'} title={feedback.msg} />
            </View>
          )}

          <View className="h-4" />
          <Button
            label="تقديم الطلب"
            size="lg"
            loading={create.isPending}
            disabled={!courseId || !oppType || reason.trim().length < 10}
            onPress={onSubmit}
          />

          <View className="h-6" />
          <SectionHeader title="الطلبات السابقة" />
          {(history ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد طلبات سابقة</Text>
            </Card>
          ) : (
            (history ?? []).map((r) => (
              <Card key={r.id} className="mb-2">
                <View className="flex-row-reverse items-center justify-between">
                  <View className="flex-1">
                    <Text className="font-kufi-bold text-ink text-[14px] text-right">
                      {r.course?.name ?? 'مقرر غير محدد'}
                    </Text>
                    <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">
                      {r.opportunity_type.label ?? '—'} · {r.course?.code ?? ''}
                    </Text>
                  </View>
                  <Pill
                    label={r.status.label}
                    tone={r.status.value === 'accepted' ? 'success' : r.status.value === 'rejected' ? 'danger' : 'warning'}
                  />
                </View>
              </Card>
            ))
          )}
        </>
      )}
    </Screen>
  );
}
