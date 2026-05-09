import { Text, View } from 'react-native';

import { Avatar, Button, Card, StatusBadge } from '@/components/primitives';
import { Screen } from '@/components/Screen';
import { useAuth } from '@/hooks/useAuth';

export default function AccountScreen() {
  const { state, logout } = useAuth();
  const student = state.status === 'authenticated' ? state.student : null;

  return (
    <Screen title="حسابي">
      <Card>
        <View className="items-center">
          <Avatar name={student?.name ?? '؟'} size={72} />
          <Text className="font-kufi-bold text-ink text-[20px] mt-3 text-center">{student?.name}</Text>
          {!!student?.specialization && (
            <Text className="font-kufi text-muted text-[14px] mt-1 text-center">{student.specialization.name}</Text>
          )}
          <Text className="font-kufi text-muted text-[13px] mt-1">رقم القيد: {student?.enrollment_no}</Text>
          <View className="mt-3">{student && <StatusBadge status={student.status} withDot />}</View>
        </View>
      </Card>

      <View className="h-4" />
      <Button label="تسجيل الخروج" variant="danger" onPress={logout} />
    </Screen>
  );
}
