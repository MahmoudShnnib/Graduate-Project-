import { useEffect, useRef } from 'react';
import { Animated, View, ViewProps } from 'react-native';

type Props = ViewProps & {
  width?: number | string;
  height?: number;
  rounded?: 'sm' | 'md' | 'lg' | 'full';
};

const radiusMap = { sm: 6, md: 10, lg: 16, full: 9999 } as const;

export function Skeleton({ width = '100%', height = 16, rounded = 'md', style, ...rest }: Props) {
  const opacity = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.9, duration: 700, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.4, duration: 700, useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={[
        { width: width as number, height, backgroundColor: '#E2E8F0', borderRadius: radiusMap[rounded], opacity },
        style,
      ]}
      {...rest}
    />
  );
}

export function SkeletonCard() {
  return (
    <View className="bg-surface border border-border rounded-2xl p-4 mb-3">
      <Skeleton width="60%" height={18} />
      <View className="h-2" />
      <Skeleton width="40%" height={14} />
      <View className="h-3" />
      <Skeleton width="100%" height={36} rounded="md" />
    </View>
  );
}
