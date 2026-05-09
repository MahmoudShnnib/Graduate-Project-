import { CloudOff } from 'lucide-react-native';
import { Text, View } from 'react-native';

import { colors } from '@/theme/tokens';

import { Button } from './Button';

type Props = {
  title?: string;
  body?: string;
  onRetry?: () => void;
};

export function ErrorState({
  title = 'تعذر تحميل البيانات',
  body = 'تأكد من اتصالك بالإنترنت ثم حاول مجدداً.',
  onRetry,
}: Props) {
  return (
    <View className="items-center py-10 px-6">
      <View className="w-16 h-16 rounded-full bg-red-50 items-center justify-center mb-4">
        <CloudOff size={28} color={colors.danger} />
      </View>
      <Text className="font-kufi-bold text-ink text-[18px] text-center">{title}</Text>
      <Text className="font-kufi text-muted text-[14px] text-center mt-2 leading-6">{body}</Text>
      {!!onRetry && (
        <View className="mt-6 w-full">
          <Button label="إعادة المحاولة" variant="outline" onPress={onRetry} />
        </View>
      )}
    </View>
  );
}
