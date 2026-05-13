import { Pressable, Text } from 'react-native';

type Props = {
  label: string;
  active: boolean;
  onPress: () => void;
};

export function SemesterChip({ label, active, onPress }: Props) {
  return (
    <Pressable
      onPress={onPress}
      className={`me-2 rounded-full px-4 py-2 active:opacity-80 ${
        active ? 'bg-primary' : 'bg-surface'
      }`}
      style={
        active
          ? {
              shadowColor: '#0066FF',
              shadowOpacity: 0.25,
              shadowRadius: 8,
              shadowOffset: { width: 0, height: 4 },
              elevation: 4,
            }
          : {
              shadowColor: '#0F172A',
              shadowOpacity: 0.05,
              shadowRadius: 6,
              shadowOffset: { width: 0, height: 1 },
              elevation: 1,
            }
      }
    >
      <Text className={`font-kufi-medium text-[13px] ${active ? 'text-white' : 'text-ink'}`}>
        {label}
      </Text>
    </Pressable>
  );
}
