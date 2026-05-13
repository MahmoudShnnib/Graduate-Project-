import { useRouter } from 'expo-router';
import { Award, FileCheck2 } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { useClearance } from '@/api/queries';
import {
  Banner,
  Button,
  Card,
  EmptyState,
  IconTile,
  SkeletonCard,
} from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { colors, tones } from '@/theme/tokens';

export default function ClearanceScreen() {
  const router = useRouter();
  const { data, isLoading } = useClearance();

  if (isLoading || !data) {
    return (
      <Screen
        title="إخلاء الطرف"
        headerVariant="hero"
        onBack={() => router.back()}
      >
        <SkeletonCard />
      </Screen>
    );
  }

  if (!data.eligible) {
    return (
      <Screen
        title="إخلاء الطرف"
        subtitle="متاح بعد إكمال المناقشة"
        headerVariant="hero"
        heroSize="md"
        overlap={28}
        onBack={() => router.back()}
      >
        <EmptyState
          icon={<Award size={36} color={colors.muted} />}
          title="إخلاء الطرف غير متاح بعد"
          body="يصبح متاحاً بعد اكتمال إجراءات المناقشة والموافقة النهائية."
        />
      </Screen>
    );
  }

  return (
    <Screen
      title="إخلاء الطرف"
      subtitle="نموذج رسمي بإكمال المتطلبات"
      headerVariant="hero"
      heroSize="lg"
      overlap={56}
      onBack={() => router.back()}
    >
      <Card elevated>
        <View className="items-center py-2">
          <IconTile tone="green" size="lg" rounded="circle">
            <FileCheck2 size={28} color={tones.green.fg} />
          </IconTile>
          <Text className="font-kufi-bold text-ink text-[18px] text-center mt-3">نموذج إخلاء الطرف</Text>
          <Text className="font-kufi text-muted text-[13px] text-center mt-1">
            وثيقة رسمية تثبت إكمال جميع المتطلبات الأكاديمية.
          </Text>
        </View>
        <View className="h-4" />
        <View className="bg-bg rounded-xl p-3.5 gap-y-2.5">
          <Row label="الاسم" value={data.student.name} />
          <Row label="رقم القيد" value={data.student.enrollment_no} />
          <Row label="التخصص" value={data.student.specialization} />
          <Row label="المشرف" value={data.student.supervisor_name} />
          {!!data.request?.issued_at && (
            <Row label="تاريخ الإصدار" value={new Date(data.request.issued_at).toLocaleDateString('ar')} />
          )}
        </View>
      </Card>

      <View className="h-3" />
      <Banner kind="success" title="تمت الموافقة على إخلاء طرفك" body="يمكنك تنزيل النموذج الرسمي أدناه." />

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
