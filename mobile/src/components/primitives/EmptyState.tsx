import { Inbox } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { colors } from '@/theme/tokens';

import { Button } from './Button';

type Props = {
  icon?: React.ReactNode;
  title: string;
  body?: string;
  actionLabel?: string;
  onAction?: () => void;
};

export function EmptyState({ icon, title, body, actionLabel, onAction }: Props) {
  return (
    <View className="items-center py-12 px-6">
      <View className="w-16 h-16 rounded-full bg-bg items-center justify-center mb-4">
        {icon ?? <Inbox size={28} color={colors.muted} />}
      </View>
      <Text className="font-kufi-bold text-ink text-[18px] text-center">{title}</Text>
      {!!body && <Text className="font-kufi text-muted text-[14px] text-center mt-2 leading-6">{body}</Text>}
      {!!actionLabel && !!onAction && (
        <View className="mt-6 w-full">
          <Button label={actionLabel} variant="outline" onPress={onAction} />
        </View>
      )}
    </View>
  );
}
