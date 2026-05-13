import { ReactNode } from 'react';
import { RefreshControl, ScrollView, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { HeroHeader } from '@/components/primitives';
import { useAuth } from '@/hooks/useAuth';
import { colors } from '@/theme/tokens';

import { PageHeader } from './PageHeader';
import { StatusBanner } from './StatusBanner';

type Props = {
  title?: string;
  subtitle?: string;
  headerRight?: ReactNode;
  children: ReactNode;
  scroll?: boolean;
  showStatusBanner?: boolean;
  headerVariant?: 'flat' | 'hero' | 'none';
  heroSize?: 'sm' | 'md' | 'lg';
  overlap?: number;
  onBack?: () => void;
  onRefresh?: () => void | Promise<void>;
  refreshing?: boolean;
};

export function Screen({
  title,
  subtitle,
  headerRight,
  children,
  scroll = true,
  showStatusBanner = true,
  headerVariant = 'flat',
  heroSize = 'md',
  overlap = 0,
  onBack,
  onRefresh,
  refreshing = false,
}: Props) {
  const insets = useSafeAreaInsets();
  const auth = useAuth();
  const student = auth.state.status === 'authenticated' ? auth.state.student : null;

  const heroEl =
    headerVariant === 'hero' && !!title ? (
      <HeroHeader
        title={title}
        subtitle={subtitle}
        right={headerRight}
        size={heroSize}
        onBack={onBack}
      />
    ) : null;

  const flatEl =
    headerVariant === 'flat' && !!title ? (
      <PageHeader title={title} subtitle={subtitle} right={headerRight} />
    ) : null;

  const refreshControl = onRefresh ? (
    <RefreshControl
      refreshing={refreshing}
      onRefresh={() => onRefresh()}
      tintColor="#FFFFFF"
      colors={[colors.primary]}
      progressBackgroundColor="#FFFFFF"
    />
  ) : undefined;

  // Inner content — negative marginTop here is allowed because it's a child View
  // (not the ScrollView's contentContainer), so RN doesn't clip the overlap.
  const content = (
    <View
      style={{
        paddingHorizontal: 16,
        paddingTop: overlap ? 0 : 16,
        paddingBottom: insets.bottom + 24,
        marginTop: overlap ? -overlap : 0,
      }}
    >
      {showStatusBanner && (
        <View className="mb-3">
          <StatusBanner student={student} />
        </View>
      )}
      {children}
    </View>
  );

  // Flat header stays sticky above the scroll view (legacy behaviour).
  if (flatEl) {
    return (
      <View className="flex-1 bg-bg" style={{ paddingTop: insets.top }}>
        {flatEl}
        {scroll ? (
          <ScrollView
            className="flex-1"
            showsVerticalScrollIndicator={false}
            refreshControl={refreshControl}
          >
            {content}
          </ScrollView>
        ) : (
          <View className="flex-1">{content}</View>
        )}
      </View>
    );
  }

  // Hero (or no header) — render inside the ScrollView so the content can
  // overlap into the hero band without being clipped.
  return (
    <View className="flex-1 bg-bg" style={{ paddingTop: insets.top }}>
      {scroll ? (
        <ScrollView
          className="flex-1"
          showsVerticalScrollIndicator={false}
          refreshControl={refreshControl}
        >
          {heroEl}
          {content}
        </ScrollView>
      ) : (
        <View className="flex-1">
          {heroEl}
          {content}
        </View>
      )}
    </View>
  );
}
