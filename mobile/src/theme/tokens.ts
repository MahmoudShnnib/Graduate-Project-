// Color + dimension tokens — keep in sync with tailwind.config.js.

export const colors = {
  primary: '#0066FF',
  primaryDark: '#0052CC',
  accent: '#F97316',
  bg: '#F5F7FA',
  surface: '#FFFFFF',
  ink: '#1E293B',
  muted: '#64748B',
  border: '#E2E8F0',
  success: '#16A34A',
  danger: '#DC2626',
  warning: '#D97706',
  info: '#0EA5E9',
} as const;

// Per-feature tonal pairs used by QuickActionTile / colored icon rows.
// Each entry is [bgTint, fg] — tint goes on the icon container, fg is the icon stroke.
export const tones = {
  red: { bg: '#FEE2E2', fg: '#DC2626' },
  blue: { bg: '#DBEAFE', fg: '#0066FF' },
  purple: { bg: '#F3E8FF', fg: '#9333EA' },
  green: { bg: '#DCFCE7', fg: '#16A34A' },
  orange: { bg: '#FFEDD5', fg: '#EA580C' },
  sky: { bg: '#E0F2FE', fg: '#0284C7' },
  amber: { bg: '#FEF3C7', fg: '#D97706' },
} as const;

export type ToneKey = keyof typeof tones;

export const fonts = {
  regular: 'NotoKufiArabic-Regular',
  medium: 'NotoKufiArabic-Medium',
  bold: 'NotoKufiArabic-Bold',
} as const;
