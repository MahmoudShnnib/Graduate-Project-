import { Text, View } from 'react-native';

import { Screen } from '@/components/Screen';

export default function HomeScreen() {
  return (
    <Screen title="الرئيسية">
      <Text className="font-kufi text-muted text-[14px]">سيتم بناء هذه الشاشة في المرحلة الثالثة.</Text>
      <View className="h-2" />
    </Screen>
  );
}
