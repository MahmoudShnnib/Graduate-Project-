import { useRouter } from 'expo-router';
import { CheckCircle2, ChevronDown, ChevronUp, Circle, Clock, XCircle } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';

import { useCourseTree } from '@/api/queries';
import type { CourseTreeRow } from '@/api/types';
import { Card, ErrorState, IconTile, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { ToneKey, colors, tones } from '@/theme/tokens';

const STATUS_TONE: Record<CourseTreeRow['status'], ToneKey> = {
  passed: 'green',
  enrolled: 'amber',
  failed: 'red',
  not_taken: 'sky',
};

const STATUS_ICON: Record<CourseTreeRow['status'], React.ComponentType<any>> = {
  passed: CheckCircle2,
  enrolled: Clock,
  failed: XCircle,
  not_taken: Circle,
};

const STATUS_LABEL: Record<CourseTreeRow['status'], string> = {
  passed: 'تم اجتيازه',
  enrolled: 'مسجل حالياً',
  failed: 'لم يتم اجتيازه',
  not_taken: 'لم يُدرس بعد',
};

function CourseRow({ row, isLast }: { row: CourseTreeRow; isLast: boolean }) {
  const Icon = STATUS_ICON[row.status];
  const tone = STATUS_TONE[row.status];
  return (
    <View className={`flex-row-reverse items-center py-3 ${isLast ? '' : 'border-b border-border'}`}>
      <IconTile tone={tone} size="md" rounded="circle">
        <Icon size={18} color={tones[tone].fg} />
      </IconTile>
      <View className="flex-1 ms-3">
        <Text className="font-kufi-bold text-ink text-[14px] text-right">{row.name}</Text>
        <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">
          {row.code} · {row.credits} ساعات · {STATUS_LABEL[row.status]}
        </Text>
      </View>
    </View>
  );
}

function Section({ title, rows }: { title: string; rows: CourseTreeRow[] }) {
  const [open, setOpen] = useState(true);
  return (
    <Card className="mb-3">
      <Pressable onPress={() => setOpen((v) => !v)} className="flex-row-reverse items-center justify-between mb-1">
        <View className="flex-row-reverse items-center gap-2">
          <Text className="font-kufi-bold text-ink text-[15px]">{title}</Text>
          <Text className="font-kufi text-muted text-[12px]">({rows.length})</Text>
        </View>
        {open ? <ChevronUp size={20} color={colors.muted} /> : <ChevronDown size={20} color={colors.muted} />}
      </Pressable>
      {open && (
        <View>
          {rows.length === 0 ? (
            <Text className="font-kufi text-muted text-[12px] text-center py-3">لا توجد مقررات</Text>
          ) : (
            rows.map((r, idx) => <CourseRow key={r.id} row={r} isLast={idx === rows.length - 1} />)
          )}
        </View>
      )}
    </Card>
  );
}

export default function CourseTreeScreen() {
  const router = useRouter();
  const { data, isLoading, isError, refetch } = useCourseTree();

  return (
    <Screen
      title="شجرة المقررات"
      subtitle="جميع مقررات تخصصك وحالة كل منها"
      headerVariant="hero"
      heroSize="md"
      overlap={28}
      onBack={() => router.back()}
    >
      {isError ? (
        <ErrorState onRetry={() => refetch()} />
      ) : isLoading || !data ? (
        <>
          <SkeletonCard />
          <SkeletonCard />
        </>
      ) : (
        <>
          <Section title="المقررات الإجبارية — العامة" rows={data.general} />
          <Section title="المقررات الإجبارية — التخصصية" rows={data.specialized} />
          <Section title="المقررات الاختيارية" rows={data.elective} />
        </>
      )}
    </Screen>
  );
}
