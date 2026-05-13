import { ReactNode } from 'react';
import { Pressable, Text } from 'react-native';

import { ToneKey, tones } from '@/theme/tokens';

import { IconTile } from './IconTile';

type Props = {
  tone: ToneKey;
  label: string;
  // Render the lucide icon — color it with tones[tone].fg in the caller.
  icon: ReactNode;
  onPress: () => void;
};

export function QuickActionTile({ tone: _tone, label, icon, onPress }: Props) {
  return (
    <Pressable
      onPress={onPress}
      className="flex-1 bg-surface rounded-2xl p-5 active:opacity-80"
      style={{
        shadowColor: '#0F172A',
        shadowOpacity: 0.06,
        shadowRadius: 8,
        shadowOffset: { width: 0, height: 2 },
        elevation: 2,
        minHeight: 116,
      }}
    >
      <IconTile tone={_tone} size="lg">
        {icon}
      </IconTile>
      <Text className="font-kufi-medium text-ink text-[14px] mt-3 text-right">{label}</Text>
    </Pressable>
  );
}

// Re-export for ergonomics
export { tones };
export type { ToneKey };
