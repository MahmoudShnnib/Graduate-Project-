import { ActivityIndicator, Pressable, PressableProps, Text, View } from 'react-native';

type Variant = 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost';
type Size = 'md' | 'lg';

type Props = Omit<PressableProps, 'children'> & {
  label: string;
  variant?: Variant;
  size?: Size;
  loading?: boolean;
  fullWidth?: boolean;
  iconLeft?: React.ReactNode;
};

const baseByVariant: Record<Variant, string> = {
  primary: 'bg-primary active:bg-primary-2',
  secondary: 'bg-primary-2 active:bg-primary',
  outline: 'bg-surface border border-border active:bg-bg',
  danger: 'bg-danger active:opacity-90',
  ghost: 'bg-transparent active:bg-bg',
};

const textByVariant: Record<Variant, string> = {
  primary: 'text-white',
  secondary: 'text-white',
  outline: 'text-ink',
  danger: 'text-white',
  ghost: 'text-primary',
};

const sizeClasses: Record<Size, { wrap: string; text: string }> = {
  md: { wrap: 'h-12 px-5 rounded-xl', text: 'text-[15px]' },
  lg: { wrap: 'h-14 px-6 rounded-xl', text: 'text-base' },
};

export function Button({
  label,
  variant = 'primary',
  size = 'md',
  loading,
  fullWidth = true,
  iconLeft,
  disabled,
  ...rest
}: Props) {
  const isDisabled = disabled || loading;
  const wrap = `${baseByVariant[variant]} ${sizeClasses[size].wrap} ${fullWidth ? 'w-full' : 'self-start'} ${
    isDisabled ? 'opacity-50' : ''
  } flex-row items-center justify-center`;

  return (
    <Pressable accessibilityRole="button" disabled={isDisabled} className={wrap} {...rest}>
      {loading ? (
        <ActivityIndicator color={variant === 'outline' || variant === 'ghost' ? '#1E293B' : '#fff'} />
      ) : (
        <View className="flex-row items-center justify-center gap-2">
          {iconLeft}
          <Text className={`font-kufi-medium ${textByVariant[variant]} ${sizeClasses[size].text}`}>{label}</Text>
        </View>
      )}
    </Pressable>
  );
}
