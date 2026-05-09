import { ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import {
  Avatar,
  Banner,
  Button,
  Card,
  EmptyState,
  Input,
  Pill,
  SectionHeader,
  Skeleton,
  SkeletonCard,
  StatusBadge,
} from '@/components/primitives';

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View className="mb-8">
      <Text className="font-kufi-bold text-ink text-[16px] text-right mb-3">{title}</Text>
      <View className="gap-y-3">{children}</View>
    </View>
  );
}

export default function DesignPreview() {
  const insets = useSafeAreaInsets();
  return (
    <ScrollView
      className="flex-1 bg-bg"
      contentContainerStyle={{ padding: 20, paddingTop: insets.top + 20, paddingBottom: insets.bottom + 40 }}
    >
      <Text className="font-kufi-bold text-ink text-[22px] text-right mb-1">معاينة نظام التصميم</Text>
      <Text className="font-kufi text-muted text-[13px] text-right mb-6">
        كل العناصر الأساسية في كل حالاتها — للمراجعة قبل بناء الشاشات.
      </Text>

      <Section title="الأزرار — Buttons">
        <Button label="زر أساسي" variant="primary" onPress={() => {}} />
        <Button label="زر ثانوي" variant="secondary" onPress={() => {}} />
        <Button label="زر مخطط" variant="outline" onPress={() => {}} />
        <Button label="زر خطر" variant="danger" onPress={() => {}} />
        <Button label="زر شفاف" variant="ghost" onPress={() => {}} />
        <Button label="زر تحميل" variant="primary" loading onPress={() => {}} />
        <Button label="زر معطل" variant="primary" disabled onPress={() => {}} />
      </Section>

      <Section title="الشارات — Pills">
        <View className="flex-row flex-wrap gap-2">
          <Pill label="مسجل" tone="success" />
          <Pill label="مسقط" tone="danger" />
          <Pill label="منسحب" tone="warning" />
          <Pill label="مستمر" tone="success" withDot />
          <Pill label="قيده موقوف" tone="warning" withDot />
          <Pill label="معلوماتي" tone="info" />
          <Pill label="رئيسي" tone="primary" />
          <Pill label="محايد" tone="neutral" />
        </View>
      </Section>

      <Section title="شارات الحالة — StatusBadge">
        <View className="flex-row flex-wrap gap-2">
          <StatusBadge status={{ value: 'enrolled', label: 'مسجل', color: 'success' }} />
          <StatusBadge status={{ value: 'rejected', label: 'مرفوض', color: 'danger' }} />
          <StatusBadge status={{ value: 'pending', label: 'قيد المراجعة', color: 'warning' }} />
          <StatusBadge status={{ value: 'approved', label: 'مقبول', color: 'success' }} withDot />
        </View>
      </Section>

      <Section title="البانرات — Banners">
        <Banner kind="on_hold" title="حسابك موقوف" body="لا يمكنك تنفيذ إجراءات الآن." />
        <Banner kind="inactive" title="حسابك في حالة انقطاع" body="تواصل مع الإدارة." />
        <Banner kind="dismissed" title="تم فصل حسابك" body="استناداً إلى تكرار الإنذارات." />
        <Banner kind="success" title="تمت الموافقة على طلبك" />
        <Banner kind="info" title="نافذة الاستطلاع مفتوحة" body="لديك حتى نهاية الأسبوع لتقديمها." />
      </Section>

      <Section title="البطاقات — Cards">
        <Card>
          <Text className="font-kufi-bold text-ink text-[16px] text-right">عنوان البطاقة</Text>
          <Text className="font-kufi text-muted text-[13px] text-right mt-1">
            وصف ثانوي للبطاقة في سطر إضافي.
          </Text>
        </Card>
        <Card>
          <View className="flex-row-reverse items-center justify-between">
            <View className="flex-1">
              <Text className="font-kufi-bold text-ink text-[16px] text-right">معالجة الإشارات الرقمية</Text>
              <Text className="font-kufi text-muted text-[13px] text-right mt-0.5">EE602 · 3 ساعات</Text>
            </View>
            <Pill label="مسجل" tone="success" />
          </View>
        </Card>
      </Section>

      <Section title="الإدخال — Inputs">
        <Input label="رقم القيد" placeholder="مثال: 26CS101" />
        <Input label="كلمة المرور" placeholder="••••••••" secureTextEntry />
        <Input label="حقل بخطأ" placeholder="..." error="هذا الحقل مطلوب" />
        <Input label="حقل بتلميح" placeholder="..." hint="استخدم البريد الجامعي." />
      </Section>

      <Section title="الصورة الرمزية — Avatar">
        <View className="flex-row gap-3">
          <Avatar name="محمود علي الترهوني" />
          <Avatar name="سامي الزروق" size={48} />
          <Avatar name="?" size={40} />
        </View>
      </Section>

      <Section title="الترويسة الفرعية — SectionHeader">
        <SectionHeader title="درجاتي" subtitle="الفصل الدراسي الأول 2026" />
      </Section>

      <Section title="حالات التحميل — Skeletons">
        <Skeleton width="60%" height={20} />
        <Skeleton width="80%" height={14} />
        <SkeletonCard />
      </Section>

      <Section title="الحالات الفارغة — Empty">
        <EmptyState
          title="لا توجد بيانات"
          body="لم نعثر على أي عنصر لعرضه. حاول تحديث الصفحة أو ارجع لاحقاً."
          actionLabel="تحديث"
          onAction={() => {}}
        />
      </Section>
    </ScrollView>
  );
}
