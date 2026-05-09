import { Clock, Lock } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';

import {
  useCreateSuspension,
  useSuspensionHistory,
  useSuspensionOptions,
} from '@/api/queries';
import { Banner, Button, Card, Input, Pill, SectionHeader, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

export default function SuspensionScreen() {
  const { data: opts, isLoading } = useSuspensionOptions();
  const { data: history } = useSuspensionHistory();
  const create = useCreateSuspension();

  const [semesterId, setSemesterId] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [feedback, setFeedback] = useState<{ tone: 'success' | 'danger'; msg: string } | null>(null);

  async function onSubmit() {
    if (!semesterId) return;
    setFeedback(null);
    try {
      await create.mutateAsync({ semester_id: semesterId, reason });
      setFeedback({ tone: 'success', msg: 'تم تقديم طلب الإيقاف' });
      setSemesterId(null);
      setReason('');
    } catch (err: any) {
      setFeedback({ tone: 'danger', msg: err?.response?.data?.message ?? 'تعذر تقديم الطلب' });
    }
  }

  return (
    <Screen title="طلب إيقاف قيد">
      {isLoading ? (
        <SkeletonCard />
      ) : (
        <>
          {opts?.state === 'approved_block' && (
            <Banner
              kind="danger"
              title="تمت الموافقة على طلب انقطاع سابق"
              body="لا يمكن تقديم طلب جديد."
              icon={<Lock size={22} color={colors.danger} />}
            />
          )}

          {opts?.state === 'pending_block' && (
            <>
              <Banner
                kind="warning"
                title="لديك طلب انقطاع قيد المراجعة"
                body="لا يمكن تقديم طلب جديد حتى تتم معالجته."
                icon={<Clock size={22} color={colors.warning} />}
              />
              {!!opts.pending && (
                <View className="mt-3">
                  <Card>
                    <Text className="font-kufi-bold text-ink text-[14px] text-right">{opts.pending.semester?.name}</Text>
                    <Text className="font-kufi text-muted text-[12px] mt-1 text-right">{opts.pending.reason}</Text>
                    <View className="mt-2 self-end">
                      <Pill label="قيد المراجعة" tone="warning" />
                    </View>
                  </Card>
                </View>
              )}
            </>
          )}

          {opts?.state === 'available' && (
            <>
              <SectionHeader title="اختر الفصل الدراسي" />
              {opts.semesters.length === 0 ? (
                <Card>
                  <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد فصول متاحة</Text>
                </Card>
              ) : (
                opts.semesters.map((s) => (
                  <Pressable
                    key={s.id}
                    disabled={s.disabled}
                    onPress={() => setSemesterId(s.id)}
                    className={`bg-surface border rounded-xl p-3.5 mb-2 ${
                      s.disabled
                        ? 'opacity-50 border-border'
                        : semesterId === s.id
                          ? 'border-primary bg-blue-50'
                          : 'border-border'
                    }`}
                  >
                    <View className="flex-row-reverse items-center justify-between">
                      <Text className="font-kufi-bold text-ink text-[14px] text-right">{s.name}</Text>
                      {s.disabled && <Lock size={18} color={colors.muted} />}
                    </View>
                    {!!s.reason && (
                      <Text className="font-kufi text-muted text-[11px] mt-1 text-right">{s.reason}</Text>
                    )}
                  </Pressable>
                ))
              )}

              <View className="h-3" />
              <Input
                label="سبب طلب الانقطاع"
                placeholder="اكتب سببك بوضوح (10 أحرف على الأقل)..."
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
                disabled={!semesterId || reason.trim().length < 10}
                onPress={onSubmit}
              />
            </>
          )}

          <View className="h-6" />
          <SectionHeader title="سجل الطلبات السابقة" />
          {(history ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد طلبات سابقة</Text>
            </Card>
          ) : (
            (history ?? []).map((h) => (
              <Card key={h.id} className="mb-2">
                <View className="flex-row-reverse items-center justify-between">
                  <View className="flex-1">
                    <Text className="font-kufi-bold text-ink text-[14px] text-right">{h.semester?.name}</Text>
                    <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right" numberOfLines={2}>
                      {h.reason}
                    </Text>
                  </View>
                  <Pill
                    label={h.status.label}
                    tone={h.status.value === 'approved' ? 'success' : h.status.value === 'rejected' ? 'danger' : 'warning'}
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
