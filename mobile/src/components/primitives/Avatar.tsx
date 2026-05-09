import { Text, View } from 'react-native';

type Props = {
  name?: string | null;
  size?: number;
};

function initials(name?: string | null): string {
  if (!name) return '؟';
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '؟';
  if (parts.length === 1) return parts[0].slice(0, 1);
  return parts[0].slice(0, 1) + parts[parts.length - 1].slice(0, 1);
}

export function Avatar({ name, size = 56 }: Props) {
  return (
    <View
      className="bg-primary items-center justify-center rounded-full"
      style={{ width: size, height: size }}
    >
      <Text className="font-kufi-bold text-white" style={{ fontSize: size * 0.4 }}>
        {initials(name)}
      </Text>
    </View>
  );
}
