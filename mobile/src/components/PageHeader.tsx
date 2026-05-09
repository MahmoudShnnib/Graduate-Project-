import { Text, View } from 'react-native';

type Props = {
  title: string;
  subtitle?: string;
  right?: React.ReactNode;
};

export function PageHeader({ title, subtitle, right }: Props) {
  return (
    <View className="bg-surface border-b border-border px-5 py-4">
      <View className="flex-row items-center justify-between">
        <View className="flex-1">
          <Text className="font-kufi-bold text-ink text-[20px] text-right">{title}</Text>
          {!!subtitle && <Text className="font-kufi text-muted text-[13px] mt-0.5 text-right">{subtitle}</Text>}
        </View>
        {right}
      </View>
    </View>
  );
}
