import { View, ViewProps } from 'react-native';

type Props = ViewProps & {
  className?: string;
  pressed?: boolean;
};

export function Card({ className = '', children, ...rest }: Props) {
  return (
    <View
      className={`bg-surface border border-border rounded-2xl px-4 py-4 ${className}`}
      style={{
        shadowColor: '#0F172A',
        shadowOpacity: 0.04,
        shadowRadius: 6,
        shadowOffset: { width: 0, height: 2 },
        elevation: 1,
      }}
      {...rest}
    >
      {children}
    </View>
  );
}
