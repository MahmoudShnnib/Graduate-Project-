import { Text, View } from 'react-native';

export type PillTone = 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'primary';

const toneClasses: Record<PillTone, { wrap: string; text: string; dot: string }> = {
  success: { wrap: 'bg-emerald-50 border-emerald-200', text: 'text-success', dot: 'bg-success' },
  danger: { wrap: 'bg-red-50 border-red-200', text: 'text-danger', dot: 'bg-danger' },
  warning: { wrap: 'bg-amber-50 border-amber-200', text: 'text-warning', dot: 'bg-warning' },
  info: { wrap: 'bg-sky-50 border-sky-200', text: 'text-info', dot: 'bg-info' },
  primary: { wrap: 'bg-blue-50 border-blue-200', text: 'text-primary', dot: 'bg-primary' },
  neutral: { wrap: 'bg-bg border-border', text: 'text-muted', dot: 'bg-muted' },
};

type Props = {
  label: string;
  tone?: PillTone;
  withDot?: boolean;
  size?: 'sm' | 'md';
};

export function Pill({ label, tone = 'neutral', withDot = false, size = 'sm' }: Props) {
  const c = toneClasses[tone];
  const padX = size === 'md' ? 'px-3' : 'px-2.5';
  const padY = size === 'md' ? 'py-1.5' : 'py-1';
  const fontSize = size === 'md' ? 'text-[13px]' : 'text-[12px]';

  return (
    <View className={`flex-row items-center self-start rounded-full border ${c.wrap} ${padX} ${padY}`}>
      {withDot && <View className={`w-1.5 h-1.5 rounded-full ms-1.5 ${c.dot}`} />}
      <Text className={`font-kufi-medium ${c.text} ${fontSize}`}>{label}</Text>
    </View>
  );
}
