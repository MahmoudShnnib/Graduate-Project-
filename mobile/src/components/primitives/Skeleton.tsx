import { useEffect, useRef } from 'react';
import { Animated, View, ViewProps } from 'react-native';

type Props = ViewProps & {
  width?: number | string;
  height?: number;
  rounded?: 'sm' | 'md' | 'lg' | 'full';
};

const radiusMap = { sm: 6, md: 10, lg: 16, full: 9999 } as const;

export function Skeleton({ width = '100%', height = 16, rounded = 'md', style, ...rest }: Props) {
  const opacity = useRef(new Animated.Value(0.5)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 1, duration: 800, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.5, duration: 800, useNativeDriver: true }),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [opacity]);

  return (
    <Animated.View
      style={[
        {
          width: width as number,
          height,
          backgroundColor: '#E2E8F0',
          borderRadius: radiusMap[rounded],
          opacity,
        },
        style,
      ]}
      {...rest}
    />
  );
}

export function SkeletonCard() {
  return (
    <View
      className="bg-surface rounded-2xl px-4 py-4 mb-3"
      style={{
        shadowColor: '#0F172A',
        shadowOpacity: 0.06,
        shadowRadius: 8,
        shadowOffset: { width: 0, height: 2 },
        elevation: 2,
      }}
    >
      <View className="flex-row-reverse items-center">
        <Skeleton width={40} height={40} rounded="full" />
        <View className="flex-1 ms-3">
          <Skeleton width="60%" height={14} />
          <View className="h-2" />
          <Skeleton width="40%" height={11} />
        </View>
      </View>
      <View className="h-3" />
      <Skeleton width="100%" height={32} rounded="md" />
    </View>
  );
}
