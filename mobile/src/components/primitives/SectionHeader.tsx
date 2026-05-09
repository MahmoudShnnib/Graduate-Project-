import { Text, View } from 'react-native';

type Props = {
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
};

export function SectionHeader({ title, subtitle, right }: Props) {
  return (
    <View className="flex-row items-center justify-between mb-3">
      <View className="flex-1">
        <Text className="font-kufi-bold text-ink text-[16px] text-right">{title}</Text>
        {!!subtitle && <Text className="font-kufi text-muted text-[12px] mt-0.5 text-right">{subtitle}</Text>}
      </View>
      {right}
    </View>
  );
}
