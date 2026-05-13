import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';

import {
  useAddDropHistory,
  useAddDropOptions,
  useCreateAddDrop,
} from '@/api/queries';
import {
  Banner,
  Button,
  Card,
  IconTile,
  Input,
  Pill,
  SectionHeader,
  SkeletonCard,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { haptic } from '@/lib/haptics';
import { ToneKey, tones } from '@/theme/tokens';

type Tab = 'new' | 'history';

function UsageStat({ label, used, max }: { label: string; used: number; max: number }) {
  const tone: ToneKey = used >= max ? 'red' : used >= 2 ? 'amber' : 'blue';
  const status = used >= max ? 'وصلت الحد' : used >= 2 ? 'قارب الانتهاء' : 'متاح';
  const pillTone = used >= max ? 'danger' : used >= 2 ? 'warning' : 'neutral';
  return (
    <Card className="flex-1">
      <Text className="font-kufi text-muted text-[12px] text-right">{label}</Text>
      <View className="flex-row-reverse items-center justify-between mt-2">
        <Text className="font-kufi-bold text-[20px]" style={{ color: tones[tone].fg }}>
          {used}/{max}
        </Text>
        <Pill label={status} tone={pillTone} />
      </View>
    </Card>
  );
}

export default function AddDropScreen() {
  const router = useRouter();
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

  async function onSubmit() {
    if (!offeringId) return;
    setFeedback(null);
    try {
      await create.mutateAsync({ course_offering_id: offeringId, type, reason: reason || undefined });
      haptic.success();
      setFeedback({ tone: 'success', msg: 'تم تقديم الطلب' });
      setOfferingId(null);
      setReason('');
    } catch (err: any) {
      haptic.error();
      setFeedback({ tone: 'danger', msg: err?.response?.data?.message ?? 'تعذر إرسال الطلب' });
    }
  }

  return (
    <Screen
      title="الإضافة والحذف"
      subtitle="إدارة مقرراتك خلال نافذة الفصل"
      headerVariant="hero"
      heroSize="md"
      overlap={28}
      onBack={() => router.back()}
    >
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
            <UsageStat label="طلبات الإضافة" used={usage.add} max={usage.max} />
            <UsageStat label="طلبات الحذف" used={usage.drop} max={usage.max} />
          </View>

          <View className="h-4" />
          {/* Segmented tabs */}
          <View className="flex-row bg-surface rounded-2xl p-1.5" style={{
            shadowColor: '#0F172A',
            shadowOpacity: 0.05,
            shadowRadius: 6,
            shadowOffset: { width: 0, height: 1 },
            elevation: 1,
          }}>
            <Pressable
              className={`flex-1 py-2.5 rounded-xl items-center ${tab === 'new' ? 'bg-primary' : ''}`}
              onPress={() => setTab('new')}
            >
              <Text className={`font-kufi-medium text-[13px] ${tab === 'new' ? 'text-white' : 'text-ink'}`}>طلب جديد</Text>
            </Pressable>
            <Pressable
              className={`flex-1 py-2.5 rounded-xl items-center ${tab === 'history' ? 'bg-primary' : ''}`}
              onPress={() => setTab('history')}
            >
              <Text className={`font-kufi-medium text-[13px] ${tab === 'history' ? 'text-white' : 'text-ink'}`}>السجل</Text>
            </Pressable>
          </View>

          <View className="h-4" />

          {tab === 'new' ? (
            <>
              {/* Type toggle (add blue / drop red) */}
              <View className="flex-row gap-3 mb-4">
                <Pressable
                  className={`flex-1 py-3 rounded-2xl items-center ${type === 'add' ? 'bg-primary' : 'bg-surface'}`}
                  onPress={() => {
                    setType('add');
                    setOfferingId(null);
                  }}
                  style={
                    type === 'add'
                      ? {
                          shadowColor: '#0066FF',
                          shadowOpacity: 0.3,
                          shadowRadius: 8,
                          shadowOffset: { width: 0, height: 4 },
                          elevation: 4,
                        }
                      : {
                          shadowColor: '#0F172A',
                          shadowOpacity: 0.05,
                          shadowRadius: 4,
                          shadowOffset: { width: 0, height: 1 },
                          elevation: 1,
                        }
                  }
                >
                  <Text className={`font-kufi-medium text-[14px] ${type === 'add' ? 'text-white' : 'text-ink'}`}>
                    إضافة مقرر
                  </Text>
                </Pressable>
                <Pressable
                  className={`flex-1 py-3 rounded-2xl items-center ${type === 'drop' ? 'bg-danger' : 'bg-surface'}`}
                  onPress={() => {
                    setType('drop');
                    setOfferingId(null);
                  }}
                  style={
                    type === 'drop'
                      ? {
                          shadowColor: '#DC2626',
                          shadowOpacity: 0.3,
                          shadowRadius: 8,
                          shadowOffset: { width: 0, height: 4 },
                          elevation: 4,
                        }
                      : {
                          shadowColor: '#0F172A',
                          shadowOpacity: 0.05,
                          shadowRadius: 4,
                          shadowOffset: { width: 0, height: 1 },
                          elevation: 1,
                        }
                  }
                >
                  <Text className={`font-kufi-medium text-[14px] ${type === 'drop' ? 'text-white' : 'text-ink'}`}>
                    حذف مقرر
                  </Text>
                </Pressable>
              </View>

              <SectionHeader
                title={type === 'add' ? 'اختر مقرراً للإضافة' : 'اختر مقرراً للحذف'}
                subtitle={`${list.length} مقرر متاح`}
              />
              <ScrollView style={{ maxHeight: 280 }}>
                {list.length === 0 ? (
                  <Card>
                    <Text className="font-kufi text-muted text-[13px] text-center py-3">لا توجد مقررات متاحة.</Text>
                  </Card>
                ) : (
                  list.map((o) => {
                    const isPicked = offeringId === o.id;
                    return (
                      <Pressable
                        key={o.id}
                        onPress={() => setOfferingId(o.id)}
                        className={`bg-surface rounded-2xl p-3.5 mb-2 flex-row-reverse items-center ${
                          isPicked ? 'bg-blue-50' : ''
                        }`}
                        style={
                          isPicked
                            ? {
                                shadowColor: '#0066FF',
                                shadowOpacity: 0.15,
                                shadowRadius: 8,
                                shadowOffset: { width: 0, height: 2 },
                                elevation: 2,
                              }
                            : {
                                shadowColor: '#0F172A',
                                shadowOpacity: 0.05,
                                shadowRadius: 6,
                                shadowOffset: { width: 0, height: 1 },
                                elevation: 1,
                              }
                        }
                      >
                        <IconTile tone={isPicked ? 'blue' : 'sky'} size="sm">
                          <View
                            className="w-3 h-3 rounded-full"
                            style={{ backgroundColor: isPicked ? tones.blue.fg : tones.sky.fg }}
                          />
                        </IconTile>
                        <View className="flex-1 ms-3">
                          <Text className="font-kufi-bold text-ink text-[14px] text-right">{o.course?.name}</Text>
                          <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{o.course?.code}</Text>
                        </View>
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
