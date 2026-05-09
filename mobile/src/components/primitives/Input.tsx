import { forwardRef } from 'react';
import { Text, TextInput, TextInputProps, View } from 'react-native';

type Props = TextInputProps & {
  label?: string;
  error?: string;
  hint?: string;
};

export const Input = forwardRef<TextInput, Props>(function Input({ label, error, hint, ...rest }, ref) {
  return (
    <View className="w-full">
      {!!label && <Text className="font-kufi-medium text-ink text-[13px] mb-1.5 text-right">{label}</Text>}
      <TextInput
        ref={ref}
        placeholderTextColor="#94A3B8"
        textAlign="right"
        className={`bg-surface border rounded-xl h-12 px-4 font-kufi text-ink text-[15px] ${
          error ? 'border-danger' : 'border-border'
        }`}
        {...rest}
      />
      {!!error && <Text className="font-kufi text-danger text-[12px] mt-1 text-right">{error}</Text>}
      {!error && !!hint && <Text className="font-kufi text-muted text-[12px] mt-1 text-right">{hint}</Text>}
    </View>
  );
});
