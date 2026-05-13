import { useRouter } from 'expo-router';
import { Check, Circle } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { useThesis } from '@/api/queries';
import type { ThesisShow } from '@/api/types';
import { Banner, Card, Pill, SectionHeader, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

const PHASES = [
  { idx: 1, title: 'تسجيل الرسالة' },
  { idx: 2, title: 'طلب المناقشة' },
  { idx: 3, title: 'جلسة المناقشة' },
  { idx: 4, title: 'الموافقة النهائية' },
];

function Stepper({ current }: { current: number }) {
  return (
    <View>
      {PHASES.map((p, i) => {
        const done = current > p.idx;
        const active = current === p.idx;
        return (
          <View key={p.idx} className="flex-row-reverse items-start">
            <View className="items-center" style={{ width: 36 }}>
              <View
                className={`w-9 h-9 rounded-full items-center justify-center ${
                  done ? 'bg-primary' : active ? 'bg-blue-50 border-2 border-primary' : 'bg-bg border border-border'
                }`}
              >
                {done ? (
                  <Check size={18} color="#fff" />
                ) : (
                  <Text className={`font-kufi-bold text-[13px] ${active ? 'text-primary' : 'text-muted'}`}>
                    {p.idx}
                  </Text>
                )}
              </View>
              {i < PHASES.length - 1 && (
                <View
                  className="w-0.5 my-1"
                  style={{ height: 28, backgroundColor: done ? colors.primary : colors.border }}
                />
              )}
            </View>
            <View className="flex-1 ms-3 pt-2">
              <Text
                className={`font-kufi-${active || done ? 'bold' : 'medium'} text-[14px] text-right ${
                  active ? 'text-primary' : done ? 'text-ink' : 'text-muted'
                }`}
              >
                {p.title}
              </Text>
            </View>
          </View>
        );
      })}
    </View>
  );
}

function EligibilityRow({ label, met, value }: { label: string; met: boolean; value: string }) {
  return (
    <View className="flex-row-reverse items-center justify-between py-2.5 border-b border-border">
      <Text className={`font-kufi-medium text-[13px] ${met ? 'text-success' : 'text-danger'}`}>{label}</Text>
      <View className="flex-row-reverse items-center gap-2">
        <Text className={`font-kufi-bold text-[13px] ${met ? 'text-success' : 'text-danger'}`}>{value}</Text>
        {met ? <Check size={16} color={colors.success} /> : <Circle size={16} color={colors.danger} />}
      </View>
    </View>
  );
}

function EligibilityCard({ data }: { data: ThesisShow['eligibility'] }) {
  return (
    <Card>
      <Text className="font-kufi-bold text-ink text-[15px] text-right">شروط تسجيل الرسالة</Text>
      <View className="mt-2">
        <EligibilityRow
          label="المعدل التراكمي"
          met={data.gpa.met}
          value={`${data.gpa.value.toFixed(2)} / ${data.gpa.required.toFixed(1)}`}
        />
        <EligibilityRow
          label="المقررات العامة"
          met={data.general.completed >= data.general.required}
          value={`${data.general.completed} / ${data.general.required}`}
        />
        <EligibilityRow
          label="المقررات التخصصية"
          met={data.specialized.completed >= data.specialized.required}
          value={`${data.specialized.completed} / ${data.specialized.required}`}
        />
        <EligibilityRow
          label="المقررات الاختيارية"
          met={data.elective.completed >= data.elective.required}
          value={`${data.elective.completed} / ${data.elective.required}`}
        />
        <EligibilityRow
          label="لا توجد درجات رسوب"
          met={data.no_failures}
          value={data.no_failures ? 'مستوفٍ' : 'غير مستوفٍ'}
        />
      </View>
    </Card>
  );
}

export default function ThesisScreen() {
  const router = useRouter();
  const { data, isLoading } = useThesis();

  if (isLoading || !data) {
    return (
      <Screen
        title="تسجيل الرسالة"
        headerVariant="hero"
        onBack={() => router.back()}
      >
        <SkeletonCard />
      </Screen>
    );
  }

  return (
    <Screen
      title="تسجيل الرسالة"
      subtitle="مراحل الرسالة والمناقشة"
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
      onBack={() => router.back()}
    >
      <Card elevated>
        <Stepper current={Math.max(1, data.phase)} />
      </Card>

      <View className="h-4" />

      {!data.registration && (
        <>
          {data.eligibility.eligible ? (
            <Banner kind="success" title="مستوفٍ لشروط التسجيل" body="يمكنك التقدّم لتسجيل رسالتك الآن." />
          ) : (
            <Banner kind="danger" title="غير مستوفٍ لشروط التسجيل" body="راجع الشروط أدناه قبل التقديم." />
          )}
          <View className="h-3" />
          <EligibilityCard data={data.eligibility} />
        </>
      )}

      {!!data.registration && (
        <>
          <SectionHeader title="بيانات الرسالة" />
          <Card>
            <Text className="font-kufi-bold text-ink text-[15px] text-right">{data.registration.title}</Text>
            {!!data.registration.title_en && (
              <Text className="font-kufi text-muted text-[12px] mt-1 text-right">{data.registration.title_en}</Text>
            )}
            <View className="mt-3 gap-y-1.5">
              <View className="flex-row-reverse items-center justify-between">
                <Text className="font-kufi text-muted text-[12px]">المشرف</Text>
                <Text className="font-kufi-medium text-ink text-[13px]">{data.registration.supervisor_name}</Text>
              </View>
              <View className="flex-row-reverse items-center justify-between">
                <Text className="font-kufi text-muted text-[12px]">الحالة</Text>
                <Pill
                  label={data.registration.status.label}
                  tone={
                    data.registration.status.color === 'success'
                      ? 'success'
                      : data.registration.status.color === 'danger'
                        ? 'danger'
                        : 'warning'
                  }
                />
              </View>
            </View>
            {!!data.registration.rejection_reason && (
              <View className="mt-3 bg-red-50 rounded-xl p-3">
                <Text className="font-kufi-bold text-danger text-[13px] text-right">سبب الرفض</Text>
                <Text className="font-kufi text-danger text-[12px] mt-1 text-right">
                  {data.registration.rejection_reason}
                </Text>
              </View>
            )}
          </Card>
        </>
      )}

      {!!data.discussion && (
        <>
          <View className="h-4" />
          <SectionHeader title="حالة المناقشة" />
          <Card>
            <View className="flex-row-reverse items-center justify-between">
              <Text className="font-kufi-medium text-ink text-[14px] text-right">المرحلة الحالية</Text>
              <Pill
                label={data.discussion.status.label}
                tone={
                  data.discussion.status.color === 'success'
                    ? 'success'
                    : data.discussion.status.color === 'danger'
                      ? 'danger'
                      : 'warning'
                }
              />
            </View>
            {!!data.discussion.discussion_date && (
              <View className="mt-2 flex-row-reverse items-center justify-between">
                <Text className="font-kufi text-muted text-[12px]">موعد المناقشة</Text>
                <Text className="font-kufi-medium text-ink text-[13px]">
                  {new Date(data.discussion.discussion_date).toLocaleDateString('ar')}
                </Text>
              </View>
            )}
            {!!data.discussion.rejection_reason && (
              <View className="mt-3 bg-red-50 rounded-xl p-3">
                <Text className="font-kufi-bold text-danger text-[13px] text-right">ملاحظات</Text>
                <Text className="font-kufi text-danger text-[12px] mt-1 text-right">
                  {data.discussion.rejection_reason}
                </Text>
              </View>
            )}
          </Card>
        </>
      )}
    </Screen>
  );
}
