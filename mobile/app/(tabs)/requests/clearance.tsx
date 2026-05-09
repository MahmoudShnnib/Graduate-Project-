import { Award, FileCheck2 } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { useClearance } from '@/api/queries';
import { Banner, Button, Card, EmptyState, SkeletonCard } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors } from '@/theme/tokens';

export default function ClearanceScreen() {
  const { data, isLoading } = useClearance();

  if (isLoading || !data) {
    return (
      <Screen title="إخلاء الطرف">
        <SkeletonCard />
      </Screen>
    );
  }

  if (!data.eligible) {
    return (
      <Screen title="إخلاء الطرف">
        <EmptyState
          icon={<Award size={36} color={colors.muted} />}
          title="إخلاء الطرف غير متاح بعد"
          body="يصبح متاحاً بعد اكتمال إجراءات المناقشة والموافقة النهائية."
        />
      </Screen>
    );
  }

  return (
    <Screen title="إخلاء الطرف">
      <Banner kind="success" title="تمت الموافقة على إخلاء طرفك" body="يمكنك تنزيل النموذج الرسمي أدناه." />
      <View className="h-4" />
      <Card>
        <View className="items-center pt-2">
          <View className="w-14 h-14 rounded-full bg-emerald-50 items-center justify-center mb-3">
            <FileCheck2 size={26} color={colors.success} />
          </View>
          <Text className="font-kufi-bold text-ink text-[18px] text-center">نموذج إخلاء الطرف</Text>
          <Text className="font-kufi text-muted text-[13px] text-center mt-1">
            وثيقة رسمية تثبت إكمال جميع المتطلبات الأكاديمية.
          </Text>
        </View>
        <View className="h-4" />
        <View className="bg-bg border border-border rounded-xl p-3 gap-y-2">
          <Row label="الاسم" value={data.student.name} />
          <Row label="رقم القيد" value={data.student.enrollment_no} />
          <Row label="التخصص" value={data.student.specialization} />
          <Row label="المشرف" value={data.student.supervisor_name} />
          {!!data.request?.issued_at && (
            <Row label="تاريخ الإصدار" value={new Date(data.request.issued_at).toLocaleDateString('ar')} />
          )}
        </View>
      </Card>

      <View className="h-4" />
      <Button label="طباعة / تنزيل PDF" size="lg" />
      <View className="h-2" />
      <Button label="مشاركة" variant="outline" size="lg" />
    </Screen>
  );
}

function Row({ label, value }: { label: string; value: string | null | undefined }) {
  return (
    <View className="flex-row-reverse items-center justify-between">
      <Text className="font-kufi text-muted text-[12px]">{label}</Text>
      <Text className="font-kufi-medium text-ink text-[13px]">{value ?? '—'}</Text>
    </View>
  );
}
