import { Tabs } from 'expo-router';
import {
  BookOpen,
  GraduationCap,
  Home,
  LayoutGrid,
  UserCircle,
} from 'lucide-react-native';
import { Platform, Pressable, Text, View } from 'react-native';

import { usePendingRequestsCount } from '@/hooks/usePendingRequestsCount';
import { haptic } from '@/lib/haptics';
import { colors } from '@/theme/tokens';

const TAB_BAR_HEIGHT = Platform.OS === 'ios' ? 88 : 72;
const RAISED_BUTTON_SIZE = 60;

function RaisedTabButton({
  onPress,
  badge,
}: {
  onPress?: (e: any) => void;
  badge?: number;
}) {
  return (
    <View
      pointerEvents="box-none"
      style={{
        flex: 1,
        alignItems: 'center',
        justifyContent: 'center',
        // Lift the button above the tab bar baseline.
        marginTop: -RAISED_BUTTON_SIZE * 0.42,
      }}
    >
      <Pressable
        onPress={(e) => {
          haptic.tap();
          onPress?.(e);
        }}
        accessibilityRole="button"
        accessibilityLabel="الطلبات"
        style={{
          width: RAISED_BUTTON_SIZE,
          height: RAISED_BUTTON_SIZE,
          borderRadius: RAISED_BUTTON_SIZE / 2,
          backgroundColor: colors.primary,
          alignItems: 'center',
          justifyContent: 'center',
          shadowColor: colors.primary,
          shadowOpacity: 0.35,
          shadowRadius: 12,
          shadowOffset: { width: 0, height: 6 },
          elevation: 10,
          borderWidth: 4,
          borderColor: '#FFFFFF',
        }}
      >
        <LayoutGrid size={26} color="#FFFFFF" />
        {!!badge && badge > 0 && (
          <View
            style={{
              position: 'absolute',
              top: -2,
              right: -2,
              minWidth: 20,
              height: 20,
              paddingHorizontal: 5,
              borderRadius: 10,
              backgroundColor: colors.warning,
              alignItems: 'center',
              justifyContent: 'center',
              borderWidth: 2,
              borderColor: '#FFFFFF',
            }}
          >
            <Text style={{ color: '#FFFFFF', fontSize: 10, fontFamily: 'NotoKufiArabic-Bold' }}>
              {badge > 99 ? '99+' : badge}
            </Text>
          </View>
        )}
      </Pressable>
      <Text
        style={{
          fontFamily: 'NotoKufiArabic-Medium',
          fontSize: 10,
          color: colors.primary,
          marginTop: 4,
        }}
      >
        الطلبات
      </Text>
    </View>
  );
}

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
          borderTopWidth: 1,
          height: TAB_BAR_HEIGHT,
          paddingBottom: Platform.OS === 'ios' ? 28 : 12,
          paddingTop: 10,
          // Lift shadow above the native border to give the tab bar depth.
          shadowColor: '#000',
          shadowOpacity: 0.06,
          shadowRadius: 10,
          shadowOffset: { width: 0, height: -2 },
          elevation: 12,
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
          // Custom raised center button. Hide the default icon/label.
          tabBarButton: (props) => (
            <RaisedTabButton onPress={props.onPress as any} badge={pendingCount} />
          ),
          tabBarLabel: () => null,
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
