import type { StatusEnum } from '@/api/types';

import { Pill, PillTone } from './Pill';

const toneByColor: Record<string, PillTone> = {
  success: 'success',
  danger: 'danger',
  warning: 'warning',
  info: 'info',
  primary: 'primary',
  gray: 'neutral',
};

const toneByValue: Record<string, PillTone> = {
  enrolled: 'success',
  dropped: 'danger',
  withdrawn: 'warning',
  approved: 'success',
  rejected: 'danger',
  pending: 'warning',
  active: 'success',
  on_hold: 'warning',
  inactive: 'warning',
  dismissed: 'danger',
  graduated: 'info',
  transferred: 'info',
};

type Props = {
  status: StatusEnum;
  withDot?: boolean;
};

export function StatusBadge({ status, withDot = false }: Props) {
  const tone =
    (status.color && toneByColor[status.color]) ||
    (status.value && toneByValue[status.value]) ||
    'neutral';
  return <Pill label={status.label} tone={tone} withDot={withDot} />;
}
