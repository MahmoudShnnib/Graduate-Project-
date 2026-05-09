// Color + dimension tokens — keep in sync with tailwind.config.js.

export const colors = {
  primary: '#2563EB',
  primary2: '#3B82F6',
  accent: '#F97316',
  bg: '#F8FAFC',
  surface: '#FFFFFF',
  ink: '#1E293B',
  muted: '#64748B',
  border: '#E2E8F0',
  success: '#16A34A',
  danger: '#DC2626',
  warning: '#D97706',
  info: '#0EA5E9',
} as const;

export const fonts = {
  regular: 'NotoKufiArabic-Regular',
  medium: 'NotoKufiArabic-Medium',
  bold: 'NotoKufiArabic-Bold',
} as const;
