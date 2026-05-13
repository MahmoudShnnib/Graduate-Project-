import { ReactNode } from 'react';
import { View } from 'react-native';

import { ToneKey, tones } from '@/theme/tokens';

type Size = 'sm' | 'md' | 'lg';

const DIM: Record<Size, { box: number; radius: number }> = {
  sm: { box: 32, radius: 8 },
  md: { box: 40, radius: 12 },
  lg: { box: 48, radius: 14 },
};

type Props = {
  tone: ToneKey;
  size?: Size;
  // Caller supplies a lucide icon node — use tones[tone].fg as its color.
  children: ReactNode;
  rounded?: 'square' | 'circle';
};

export function IconTile({ tone, size = 'md', children, rounded = 'square' }: Props) {
  const { bg } = tones[tone];
  const { box, radius } = DIM[size];
  return (
    <View
      className="items-center justify-center"
      style={{
        width: box,
        height: box,
        borderRadius: rounded === 'circle' ? box / 2 : radius,
        backgroundColor: bg,
      }}
    >
      {children}
    </View>
  );
}
