import { View, ViewProps } from 'react-native';

type Props = ViewProps & {
  className?: string;
  elevated?: boolean;
};

const SHADOW_FLAT = {
  shadowColor: '#0F172A',
  shadowOpacity: 0.06,
  shadowRadius: 8,
  shadowOffset: { width: 0, height: 2 },
  elevation: 2,
} as const;

const SHADOW_ELEVATED = {
  shadowColor: '#0F172A',
  shadowOpacity: 0.12,
  shadowRadius: 18,
  shadowOffset: { width: 0, height: 8 },
  elevation: 8,
} as const;

export function Card({ className = '', elevated = false, children, ...rest }: Props) {
  return (
    <View
      className={`bg-surface rounded-2xl px-4 py-4 ${className}`}
      style={elevated ? SHADOW_ELEVATED : SHADOW_FLAT}
      {...rest}
    >
      {children}
    </View>
  );
}
