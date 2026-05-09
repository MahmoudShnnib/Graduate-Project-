import type { Student } from '@/api/types';

import { Banner } from './primitives';

type Props = { student: Student | null | undefined };

export function StatusBanner({ student }: Props) {
  if (!student) return null;
  switch (student.status.value) {
    case 'on_hold':
      return (
        <Banner
          kind="on_hold"
          title="حسابك موقوف حالياً"
          body="بناءً على طلب إيقاف قيد تمت الموافقة عليه. لا يمكنك تنفيذ إجراءات حتى يُعاد تفعيل الحساب."
        />
      );
    case 'inactive':
      return (
        <Banner
          kind="inactive"
          title="حسابك في حالة انقطاع"
          body="لم يتم تقديم استطلاع المقررات في الوقت المحدد. تواصل مع الإدارة لإعادة التفعيل."
        />
      );
    case 'dismissed':
      return (
        <Banner
          kind="dismissed"
          title="تم فصل حسابك"
          body="استناداً إلى تكرار الإنذارات. يرجى التواصل مع الإدارة للاستفسار."
        />
      );
    default:
      return null;
  }
}
