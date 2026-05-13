import { ReactNode } from 'react';
import { Pressable, Text, View } from 'react-native';
import { ChevronRight } from 'lucide-react-native';

type Props = {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  right?: ReactNode;
  // Extra bottom padding so the page below can pull content up via -mt-*.
  // Defaults to 'md' (good for stat cards). Use 'lg' for big overlapping cards.
  size?: 'sm' | 'md' | 'lg';
};

const PADDING_BOTTOM: Record<NonNullable<Props['size']>, string> = {
  sm: 'pb-6',
  md: 'pb-12',
  lg: 'pb-20',
};

export function HeroHeader({ title, subtitle, onBack, right, size = 'md' }: Props) {
  return (
    <View className={`bg-primary px-5 pt-5 ${PADDING_BOTTOM[size]}`}>
      <View className="flex-row items-center gap-3">
        {!!onBack && (
          <Pressable hitSlop={12} onPress={onBack}>
            {/* Chevron points right under RTL = visual "back" */}
            <ChevronRight size={26} color="#fff" />
          </Pressable>
        )}
        <View className="flex-1">
          <Text className="font-kufi-bold text-white text-[22px] text-right">{title}</Text>
          {!!subtitle && (
            <Text className="font-kufi text-white/80 text-[13px] mt-1 text-right">{subtitle}</Text>
          )}
        </View>
        {right}
      </View>
    </View>
  );
}
