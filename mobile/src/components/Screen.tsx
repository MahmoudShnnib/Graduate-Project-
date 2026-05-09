import { ReactNode } from 'react';
import { ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useAuth } from '@/hooks/useAuth';

import { PageHeader } from './PageHeader';
import { StatusBanner } from './StatusBanner';

type Props = {
  title?: string;
  subtitle?: string;
  headerRight?: ReactNode;
  children: ReactNode;
  scroll?: boolean;
  showStatusBanner?: boolean;
};

export function Screen({ title, subtitle, headerRight, children, scroll = true, showStatusBanner = true }: Props) {
  const insets = useSafeAreaInsets();
  const auth = useAuth();
  const student = auth.state.status === 'authenticated' ? auth.state.student : null;

  const Wrapper = scroll ? ScrollView : View;

  return (
    <View className="flex-1 bg-bg" style={{ paddingTop: insets.top }}>
      {!!title && <PageHeader title={title} subtitle={subtitle} right={headerRight} />}
      <Wrapper
        className="flex-1"
        contentContainerStyle={{ padding: 16, paddingBottom: insets.bottom + 24 }}
        showsVerticalScrollIndicator={false}
      >
        {showStatusBanner && (
          <View className="mb-3">
            <StatusBanner student={student} />
          </View>
        )}
        {children}
      </Wrapper>
    </View>
  );
}
