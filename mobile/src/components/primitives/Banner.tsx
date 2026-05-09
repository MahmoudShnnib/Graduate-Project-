import { AlertCircle, AlertTriangle, CheckCircle2, Info, Lock } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { colors } from '@/theme/tokens';

export type BannerTone = 'success' | 'danger' | 'warning' | 'info' | 'neutral';
export type BannerKind = 'on_hold' | 'inactive' | 'dismissed' | 'success' | 'info' | 'warning' | 'danger';

const toneStyle: Record<BannerTone, { bg: string; text: string; icon: string }> = {
  success: { bg: 'bg-emerald-50 border-emerald-200', text: 'text-success', icon: colors.success },
  danger: { bg: 'bg-red-50 border-red-200', text: 'text-danger', icon: colors.danger },
  warning: { bg: 'bg-amber-50 border-amber-200', text: 'text-warning', icon: colors.warning },
  info: { bg: 'bg-sky-50 border-sky-200', text: 'text-info', icon: colors.info },
  neutral: { bg: 'bg-bg border-border', text: 'text-ink', icon: colors.muted },
};

type Props = {
  tone?: BannerTone;
  kind?: BannerKind;
  title: string;
  body?: string;
  icon?: React.ReactNode;
};

function inferToneAndIcon(kind?: BannerKind, tone?: BannerTone) {
  if (tone) return { tone, IconCmp: pickIcon(tone) };
  switch (kind) {
    case 'on_hold':
      return { tone: 'warning' as BannerTone, IconCmp: Lock };
    case 'inactive':
      return { tone: 'warning' as BannerTone, IconCmp: AlertTriangle };
    case 'dismissed':
      return { tone: 'danger' as BannerTone, IconCmp: AlertCircle };
    case 'success':
      return { tone: 'success' as BannerTone, IconCmp: CheckCircle2 };
    case 'danger':
      return { tone: 'danger' as BannerTone, IconCmp: AlertCircle };
    case 'warning':
      return { tone: 'warning' as BannerTone, IconCmp: AlertTriangle };
    default:
      return { tone: 'info' as BannerTone, IconCmp: Info };
  }
}

function pickIcon(tone: BannerTone) {
  switch (tone) {
    case 'success':
      return CheckCircle2;
    case 'danger':
      return AlertCircle;
    case 'warning':
      return AlertTriangle;
    case 'info':
      return Info;
    default:
      return Info;
  }
}

export function Banner({ tone, kind, title, body, icon }: Props) {
  const inferred = inferToneAndIcon(kind, tone);
  const t = toneStyle[inferred.tone];
  const IconCmp = inferred.IconCmp;
  return (
    <View className={`flex-row items-start border rounded-2xl p-3.5 ${t.bg}`}>
      {icon ?? <IconCmp size={22} color={t.icon} />}
      <View className="flex-1 ms-3">
        <Text className={`font-kufi-bold text-[14px] ${t.text}`}>{title}</Text>
        {!!body && <Text className={`font-kufi text-[13px] mt-0.5 ${t.text} opacity-90`}>{body}</Text>}
      </View>
    </View>
  );
}
