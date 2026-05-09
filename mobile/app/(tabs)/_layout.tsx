import { Tabs } from 'expo-router';
import { BookOpen, FileText, GraduationCap, Home, UserCircle } from 'lucide-react-native';
import { Platform } from 'react-native';

import { usePendingRequestsCount } from '@/hooks/usePendingRequestsCount';
import { colors } from '@/theme/tokens';

export default function TabsLayout() {
  const { data: pendingCount } = usePendingRequestsCount();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopColor: colors.border,
          height: Platform.OS === 'ios' ? 88 : 70,
          paddingBottom: Platform.OS === 'ios' ? 28 : 10,
          paddingTop: 8,
        },
        tabBarLabelStyle: {
          fontFamily: 'NotoKufiArabic-Medium',
          fontSize: 11,
          marginTop: 2,
        },
        tabBarBadgeStyle: {
          fontFamily: 'NotoKufiArabic-Medium',
          fontSize: 10,
          backgroundColor: colors.warning,
        },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: 'الرئيسية',
          tabBarIcon: ({ color, size }) => <Home color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="enrollments"
        options={{
          title: 'مقرراتي',
          tabBarIcon: ({ color, size }) => <BookOpen color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="requests"
        options={{
          title: 'الطلبات',
          tabBarIcon: ({ color, size }) => <FileText color={color} size={size} />,
          tabBarBadge: pendingCount && pendingCount > 0 ? pendingCount : undefined,
        }}
      />
      <Tabs.Screen
        name="grades"
        options={{
          title: 'الدرجات',
          tabBarIcon: ({ color, size }) => <GraduationCap color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'حسابي',
          tabBarIcon: ({ color, size }) => <UserCircle color={color} size={size} />,
        }}
      />
    </Tabs>
  );
}
