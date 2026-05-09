/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/**/*.{js,ts,tsx}', './src/**/*.{js,ts,tsx}'],
  presets: [require('nativewind/preset')],
  theme: {
    extend: {
      colors: {
        primary: '#2563EB',
        'primary-2': '#3B82F6',
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
      },
      fontFamily: {
        kufi: ['NotoKufiArabic-Regular'],
        'kufi-medium': ['NotoKufiArabic-Medium'],
        'kufi-bold': ['NotoKufiArabic-Bold'],
      },
      borderRadius: {
        '2xl': '16px',
        xl: '12px',
        full: '9999px',
      },
    },
  },
  plugins: [],
};
