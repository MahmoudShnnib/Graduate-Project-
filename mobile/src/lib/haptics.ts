import * as Haptics from 'expo-haptics';
import { Platform } from 'react-native';

// Centralized haptic feedback. Calls are silently swallowed on web/unsupported
// platforms — using a thin wrapper avoids try/catch noise at every call site.
//
// Usage guideline (UI/UX skill):
//   - selection — for tab switches / segmented control changes
//   - tap       — for primary CTAs and FAB-style buttons
//   - success   — after a mutation succeeds
//   - warning   — for destructive confirmations
//   - error     — when a submission fails

const supported = Platform.OS === 'ios' || Platform.OS === 'android';

function safe(fn: () => Promise<void>) {
  if (!supported) return;
  fn().catch(() => undefined);
}

export const haptic = {
  selection: () => safe(() => Haptics.selectionAsync()),
  tap: () => safe(() => Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light)),
  medium: () => safe(() => Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium)),
  success: () => safe(() => Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success)),
  warning: () => safe(() => Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning)),
  error: () => safe(() => Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error)),
};
