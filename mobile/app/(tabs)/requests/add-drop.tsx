import { useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';

import {
  useAddDropHistory,
  useAddDropOptions,
  useCreateAddDrop,
} from '@/api/queries';
import { Banner, Button, Card, Input, Pill, SectionHeader, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

type Tab = 'new' | 'history';

export default function AddDropScreen() {
  const { data: opts, isLoading: optsLoading } = useAddDropOptions();
  const { data: history, isLoading: histLoading } = useAddDropHistory();
  const create = useCreateAddDrop();

  const [tab, setTab] = useState<Tab>('new');
  const [type, setType] = useState<'add' | 'drop'>('add');
  const [offeringId, setOfferingId] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [feedback, setFeedback] = useState<{ tone: 'success' | 'danger'; msg: string } | null>(null);

  const list = type === 'add' ? opts?.addable.data ?? [] : (opts?.droppable.data ?? []).map((e) => e.course_offering);
  const usage = opts?.usage ?? { add: 0, drop: 0, max: 3 };
  const used = type === 'add' ? usage.add : usage.drop;
  const usageTone = used >= 3 ? 'danger' : used >= 2 ? 'warning' : 'neutral';

  async function onSubmit() {
    if (!offeringId) return;
    setFeedback(null);
    try {
      await create.mutateAsync({ course_offering_id: offeringId, type, reason: reason || undefined });
      setFeedback({ tone: 'success', msg: 'تم تقديم الطلب' });
      setOfferingId(null);
      setReason('');
    } catch (err: any) {
      setFeedback({ tone: 'danger', msg: err?.response?.data?.message ?? 'تعذر إرسال الطلب' });
    }
  }

  return (
    <Screen title="الإضافة والحذف">
      {optsLoading ? (
        <SkeletonCard />
      ) : (
        <>
          <Banner
            kind={opts?.window_open ? 'success' : 'danger'}
            title={opts?.window_open ? 'نافذة الإضافة والحذف مفتوحة' : 'نافذة الإضافة والحذف مغلقة'}
          />
          <View className="h-3" />
          <View className="flex-row gap-3">
            <View className="flex-1 bg-surface border border-border rounded-xl p-3">
              <Text className="font-kufi text-muted text-[12px] text-right">طلبات الإضافة</Text>
              <View className="mt-1 flex-row-reverse items-center justify-between">
                <Text className="font-kufi-bold text-ink text-[18px]">{usage.add}/{usage.max}</Text>
                <Pill label={usage.add >= 3 ? 'وصلت الحد' : usage.add >= 2 ? 'قارب الانتهاء' : 'متاح'} tone={usage.add >= 3 ? 'danger' : usage.add >= 2 ? 'warning' : 'neutral'} />
              </View>
            </View>
            <View className="flex-1 bg-surface border border-border rounded-xl p-3">
              <Text className="font-kufi text-muted text-[12px] text-right">طلبات الحذف</Text>
              <View className="mt-1 flex-row-reverse items-center justify-between">
                <Text className="font-kufi-bold text-ink text-[18px]">{usage.drop}/{usage.max}</Text>
                <Pill label={usage.drop >= 3 ? 'وصلت الحد' : usage.drop >= 2 ? 'قارب الانتهاء' : 'متاح'} tone={usage.drop >= 3 ? 'danger' : usage.drop >= 2 ? 'warning' : 'neutral'} />
              </View>
            </View>
          </View>

          <View className="h-4" />
          <View className="flex-row bg-surface border border-border rounded-xl p-1">
            <Pressable
              className={`flex-1 py-2.5 rounded-lg items-center ${tab === 'new' ? 'bg-primary' : ''}`}
              onPress={() => setTab('new')}
            >
              <Text className={`font-kufi-medium text-[13px] ${tab === 'new' ? 'text-white' : 'text-ink'}`}>طلب جديد</Text>
            </Pressable>
            <Pressable
              className={`flex-1 py-2.5 rounded-lg items-center ${tab === 'history' ? 'bg-primary' : ''}`}
              onPress={() => setTab('history')}
            >
              <Text className={`font-kufi-medium text-[13px] ${tab === 'history' ? 'text-white' : 'text-ink'}`}>السجل</Text>
            </Pressable>
          </View>

          <View className="h-4" />

          {tab === 'new' ? (
            <>
              <View className="flex-row bg-surface border border-border rounded-xl p-1 mb-3">
                <Pressable
                  className={`flex-1 py-2.5 rounded-lg items-center ${type === 'add' ? 'bg-primary' : ''}`}
                  onPress={() => {
                    setType('add');
                    setOfferingId(null);
                  }}
                >
                  <Text className={`font-kufi-medium text-[13px] ${type === 'add' ? 'text-white' : 'text-ink'}`}>إضافة</Text>
                </Pressable>
                <Pressable
                  className={`flex-1 py-2.5 rounded-lg items-center ${type === 'drop' ? 'bg-danger' : ''}`}
                  onPress={() => {
                    setType('drop');
                    setOfferingId(null);
                  }}
                >
                  <Text className={`font-kufi-medium text-[13px] ${type === 'drop' ? 'text-white' : 'text-ink'}`}>حذف</Text>
                </Pressable>
              </View>

              <SectionHeader
                title={type === 'add' ? 'اختر مقرراً للإضافة' : 'اختر مقرراً للحذف'}
                subtitle={`${list.length} مقرر متاح`}
              />
              <ScrollView style={{ maxHeight: 240 }}>
                {list.length === 0 ? (
                  <Text className="font-kufi text-muted text-[13px] text-center py-6">لا توجد مقررات متاحة.</Text>
                ) : (
                  list.map((o) => {
                    const isPicked = offeringId === o.id;
                    return (
                      <Pressable
                        key={o.id}
                        onPress={() => setOfferingId(o.id)}
                        className={`bg-surface border rounded-xl p-3 mb-2 ${isPicked ? 'border-primary bg-blue-50' : 'border-border'}`}
                      >
                        <Text className="font-kufi-bold text-ink text-[14px] text-right">{o.course?.name}</Text>
                        <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{o.course?.code}</Text>
                      </Pressable>
                    );
                  })
                )}
              </ScrollView>

              <View className="h-3" />
              <Input
                label={type === 'add' ? 'سبب الإضافة (مطلوب)' : 'سبب الحذف (اختياري)'}
                placeholder="اكتب سبب الطلب..."
                value={reason}
                onChangeText={setReason}
                multiline
                numberOfLines={3}
                style={{ height: 80, textAlignVertical: 'top' as any, paddingTop: 12 }}
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
                disabled={!offeringId || !opts?.window_open || (type === 'add' && reason.trim().length < 1)}
                onPress={onSubmit}
              />
            </>
          ) : histLoading ? (
            <SkeletonCard />
          ) : (history ?? []).length === 0 ? (
            <Card>
              <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد طلبات سابقة</Text>
            </Card>
          ) : (
            (history ?? []).map((r) => (
              <Card key={r.id} className="mb-2">
                <View className="flex-row-reverse items-center justify-between">
                  <View className="flex-1">
                    <Text className="font-kufi-bold text-ink text-[14px] text-right">{r.course_offering.course?.name}</Text>
                    <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">{r.course_offering.course?.code}</Text>
                    {!!r.reason && (
                      <Text className="font-kufi text-muted text-[12px] mt-1 text-right" numberOfLines={2}>
                        {r.reason}
                      </Text>
                    )}
                  </View>
                  <View className="ms-2 gap-1 items-end">
                    <Pill label={r.type.label} tone={r.type.value === 'add' ? 'primary' : 'danger'} />
                    <Pill
                      label={r.status.label}
                      tone={r.status.value === 'approved' ? 'success' : r.status.value === 'rejected' ? 'danger' : 'warning'}
                    />
                  </View>
                </View>
              </Card>
            ))
          )}
        </>
      )}
    </Screen>
  );
}
