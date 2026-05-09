import { useQuery } from '@tanstack/react-query';

import { apiClient } from '@/api/client';
import type { AddDropRequest, ExceptionalRequest, SuspensionRequest } from '@/api/types';

type ApiList<T> = { data: T[] };

async function get<T>(path: string): Promise<T> {
  const { data } = await apiClient.get<T>(path);
  return data;
}

/**
 * Sum of pending requests across add-drop, suspension, and exceptional —
 * used to drive the badge on the الطلبات bottom tab.
 */
export function usePendingRequestsCount() {
  return useQuery({
    queryKey: ['pending-count'],
    refetchInterval: 60_000,
    queryFn: async () => {
      const [addDrop, suspension, exceptional] = await Promise.all([
        get<ApiList<AddDropRequest>>('/add-drop-requests'),
        get<ApiList<SuspensionRequest>>('/suspension-requests'),
        get<ApiList<ExceptionalRequest>>('/exceptional-requests'),
      ]);
      const a = addDrop.data.filter((r) => r.status.value === 'pending').length;
      const s = suspension.data.filter((r) => r.status.value === 'pending').length;
      const e = exceptional.data.filter((r) => r.status.value === 'pending').length;
      return a + s + e;
    },
  });
}
