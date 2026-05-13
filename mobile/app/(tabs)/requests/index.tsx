import { useRouter } from 'expo-router';
import {
  Award,
  CalendarX,
  ClipboardList,
  FileEdit,
  RotateCw,
  ScrollText,
  TreePine,
} from 'lucide-react-native';
import { Pressable, Text, View } from 'react-native';

import {
  useActiveSemester,
  useAddDropHistory,
  useExceptionalHistory,
  useSuspensionHistory,
} from '@/api/queries';
import {
  Card,
  IconTile,
  Pill,
  SectionHeader,
  SkeletonCard,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { ToneKey, tones } from '@/theme/tokens';

type ActionTone = ToneKey;

type Action = {
  key: string;
  title: string;
  body: string;
  tone: ActionTone;
  icon: React.ReactNode;
  href:
    | '/(tabs)/requests/survey'
    | '/(tabs)/requests/add-drop'
    | '/(tabs)/requests/suspension'
    | '/(tabs)/requests/exceptional'
    | '/(tabs)/requests/thesis'
    | '/(tabs)/requests/course-tree'
    | '/(tabs)/requests/clearance';
};

function ActionCard({
  action,
  badge,
  onPress,
}: {
  action: Action;
  badge?: string;
  onPress: () => void;
}) {
  return (
    <Pressable
      onPress={onPress}
      className="flex-1 bg-surface rounded-2xl p-4 active:opacity-80"
      style={{
        shadowColor: '#0F172A',
        shadowOpacity: 0.06,
        shadowRadius: 8,
        shadowOffset: { width: 0, height: 2 },
        elevation: 2,
        minHeight: 144,
      }}
    >
      <View className="flex-row-reverse items-start justify-between">
        <IconTile tone={action.tone} size="lg">
          {action.icon}
        </IconTile>
        {!!badge && <Pill label={badge} tone="warning" />}
      </View>
      <Text className="font-kufi-bold text-ink text-[14px] mt-3 text-right">{action.title}</Text>
      <Text className="font-kufi text-muted text-[11px] mt-1 text-right" numberOfLines={2}>
        {action.body}
      </Text>
    </Pressable>
  );
}

export default function RequestsHub() {
  const router = useRouter();
  const { data: semester } = useActiveSemester();
  const { data: addDropHistory, isLoading: addDropLoading } = useAddDropHistory();
  const { data: suspensionHistory } = useSuspensionHistory();
  const { data: exceptionalHistory } = useExceptionalHistory();

  const pendingAddDrop = (addDropHistory ?? []).filter((r) => r.status.value === 'pending').length;
  const pendingSuspension = (suspensionHistory ?? []).filter((r) => r.status.value === 'pending').length;
  const pendingExceptional = (exceptionalHistory ?? []).filter((r) => r.status.value === 'pending').length;

  const actions: Action[] = [
    {
      key: 'survey',
      title: 'استطلاع المقررات',
      body: 'رتّب المقررات للفصل القادم.',
      tone: 'blue',
      icon: <ClipboardList size={26} color={tones.blue.fg} />,
      href: '/(tabs)/requests/survey',
    },
    {
      key: 'add-drop',
      title: 'الإضافة والحذف',
      body: 'أضف أو احذف مقرر هذا الفصل.',
      tone: 'purple',
      icon: <FileEdit size={26} color={tones.purple.fg} />,
      href: '/(tabs)/requests/add-drop',
    },
    {
      key: 'suspension',
      title: 'إيقاف القيد',
      body: 'قدّم طلب إيقاف لفصل دراسي.',
      tone: 'orange',
      icon: <CalendarX size={26} color={tones.orange.fg} />,
      href: '/(tabs)/requests/suspension',
    },
    {
      key: 'exceptional',
      title: 'الفرص الاستثنائية',
      body: 'فرصة لمقرر سبق رسوبك فيه.',
      tone: 'red',
      icon: <RotateCw size={26} color={tones.red.fg} />,
      href: '/(tabs)/requests/exceptional',
    },
    {
      key: 'thesis',
      title: 'تسجيل الرسالة',
      body: 'تتبّع مراحل الرسالة والمناقشة.',
      tone: 'green',
      icon: <ScrollText size={26} color={tones.green.fg} />,
      href: '/(tabs)/requests/thesis',
    },
    {
      key: 'course-tree',
      title: 'شجرة المقررات',
      body: 'استعرض مقررات تخصصك.',
      tone: 'sky',
      icon: <TreePine size={26} color={tones.sky.fg} />,
      href: '/(tabs)/requests/course-tree',
    },
    {
      key: 'clearance',
      title: 'إخلاء الطرف',
      body: 'متاح بعد إكمال المناقشة.',
      tone: 'amber',
      icon: <Award size={26} color={tones.amber.fg} />,
      href: '/(tabs)/requests/clearance',
    },
  ];

  const badges: Record<string, string | undefined> = {
    'add-drop': pendingAddDrop > 0 ? `${pendingAddDrop} قيد المراجعة` : undefined,
    suspension: pendingSuspension > 0 ? `${pendingSuspension} قيد المراجعة` : undefined,
    exceptional: pendingExceptional > 0 ? `${pendingExceptional} قيد المراجعة` : undefined,
  };

  // Lay out as 2-col rows. Last action (clearance) gets a full-width row.
  const rows: Action[][] = [];
  for (let i = 0; i < actions.length - 1; i += 2) {
    rows.push(actions.slice(i, i + 2));
  }
  const lastAction = actions[actions.length - 1];

  return (
    <Screen
      title="الطلبات والإجراءات"
      subtitle="كل ما يخص مسارك الأكاديمي"
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
    >
      {/* Status summary */}
      {!!semester && (
        <Card elevated>
          <View className="flex-row-reverse items-center justify-between">
            <Text className="font-kufi-bold text-ink text-[15px] text-right flex-1">{semester.name}</Text>
          </View>
          <View className="flex-row gap-2 flex-wrap mt-3">
            <Pill
              label={semester.survey_open ? 'الاستطلاع مفتوح' : 'الاستطلاع مغلق'}
              tone={semester.survey_open ? 'success' : 'neutral'}
              withDot
            />
            <Pill
              label={semester.add_drop_open ? 'الإضافة/الحذف مفتوح' : 'الإضافة/الحذف مغلق'}
              tone={semester.add_drop_open ? 'success' : 'neutral'}
              withDot
            />
          </View>
        </Card>
      )}

      {/* Action grid */}
      <View className="h-5" />
      <SectionHeader title="جميع الإجراءات" />
      {rows.map((row, idx) => (
        <View key={idx} className="flex-row gap-3 mb-3">
          {row.map((a) => (
            <ActionCard
              key={a.key}
              action={a}
              badge={badges[a.key]}
              onPress={() => router.push(a.href)}
            />
          ))}
        </View>
      ))}
      <View className="flex-row gap-3 mb-3">
        <ActionCard
          action={lastAction}
          badge={badges[lastAction.key]}
          onPress={() => router.push(lastAction.href)}
        />
        {/* Empty filler so the lone tile keeps its half-width sizing for visual balance */}
        <View className="flex-1" />
      </View>

      {/* Recent activity */}
      <View className="h-3" />
      <SectionHeader title="آخر طلبات الإضافة/الحذف" />
      {addDropLoading ? (
        <SkeletonCard />
      ) : (addDropHistory ?? []).length === 0 ? (
        <Card>
          <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد طلبات سابقة</Text>
        </Card>
      ) : (
        (addDropHistory ?? []).slice(0, 3).map((r) => (
          <Card key={r.id} className="mb-2">
            <View className="flex-row-reverse items-center justify-between">
              <View className="flex-1">
                <Text className="font-kufi-bold text-ink text-[14px] text-right">{r.course_offering.course?.name}</Text>
                <Text className="font-kufi text-muted text-[11px] mt-0.5 text-right">{r.course_offering.course?.code}</Text>
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
    </Screen>
  );
}
