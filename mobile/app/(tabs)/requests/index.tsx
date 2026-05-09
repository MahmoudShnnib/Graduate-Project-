import { useRouter } from 'expo-router';
import { CalendarX, ChevronLeft, ClipboardList, FileEdit, RotateCw, ScrollText, TreePine } from 'lucide-react-native';
import { Pressable, Text, View } from 'react-native';

import { useAddDropHistory } from '@/api/queries';
import { Card, Pill, SectionHeader, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

function NavItem({
  icon,
  title,
  body,
  onPress,
  badge,
}: {
  icon: React.ReactNode;
  title: string;
  body: string;
  onPress: () => void;
  badge?: string;
}) {
  return (
    <Pressable
      onPress={onPress}
      className="bg-surface border border-border rounded-2xl p-4 mb-3 flex-row-reverse items-center active:bg-bg"
    >
      <View className="w-11 h-11 rounded-full bg-blue-50 items-center justify-center">{icon}</View>
      <View className="flex-1 ms-3">
        <View className="flex-row-reverse items-center gap-2">
          <Text className="font-kufi-bold text-ink text-[15px] text-right">{title}</Text>
          {!!badge && <Pill label={badge} tone="warning" />}
        </View>
        <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{body}</Text>
      </View>
      <ChevronLeft size={20} color={colors.muted} />
    </Pressable>
  );
}

export default function RequestsLanding() {
  const router = useRouter();
  const { data: history, isLoading } = useAddDropHistory();

  const pending = (history ?? []).filter((r) => r.status.value === 'pending');

  return (
    <Screen title="الطلبات">
      <NavItem
        icon={<ClipboardList size={22} color={colors.primary} />}
        title="استطلاع المقررات"
        body="رتّب المقررات التي ترغب بدراستها للفصل القادم."
        onPress={() => router.push('/(tabs)/requests/survey')}
      />
      <NavItem
        icon={<FileEdit size={22} color={colors.primary} />}
        title="الإضافة والحذف"
        body="قدّم طلب إضافة أو حذف مقرر خلال نافذة الفصل."
        onPress={() => router.push('/(tabs)/requests/add-drop')}
        badge={pending.length > 0 ? `${pending.length} قيد المراجعة` : undefined}
      />
      <NavItem
        icon={<CalendarX size={22} color={colors.primary} />}
        title="إيقاف القيد"
        body="قدّم طلب إيقاف قيد لفصل دراسي محدد."
        onPress={() => router.push('/(tabs)/requests/suspension')}
      />
      <NavItem
        icon={<RotateCw size={22} color={colors.primary} />}
        title="الفرص الاستثنائية"
        body="اطلب فرصة استثنائية لمقرر رسبت فيه."
        onPress={() => router.push('/(tabs)/requests/exceptional')}
      />
      <NavItem
        icon={<ScrollText size={22} color={colors.primary} />}
        title="تسجيل الرسالة والمناقشة"
        body="تتبّع مراحل تسجيل الرسالة والمناقشة والموافقة النهائية."
        onPress={() => router.push('/(tabs)/requests/thesis')}
      />
      <NavItem
        icon={<TreePine size={22} color={colors.primary} />}
        title="شجرة المقررات"
        body="استعرض جميع مقررات تخصصك وحالة كل منها."
        onPress={() => router.push('/(tabs)/requests/course-tree')}
      />

      <View className="h-2" />
      <SectionHeader title="آخر طلبات الإضافة/الحذف" />
      {isLoading ? (
        <SkeletonCard />
      ) : (history ?? []).length === 0 ? (
        <Card>
          <Text className="font-kufi text-muted text-[13px] text-center py-2">لا توجد طلبات سابقة</Text>
        </Card>
      ) : (
        (history ?? []).slice(0, 3).map((r) => (
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

      <View className="h-3" />
      <Pressable
        onPress={() => router.push('/(tabs)/requests/clearance')}
        className="bg-bg border border-border rounded-2xl p-4 active:opacity-70"
      >
        <Text className="font-kufi-bold text-ink text-[15px] text-right">إخلاء الطرف</Text>
        <Text className="font-kufi text-muted text-[12px] mt-1 text-right">
          يصبح متاحاً بعد إكمال إجراءات المناقشة.
        </Text>
      </Pressable>
    </Screen>
  );
}
