/**
 * Notifications store — Zustand.
 *
 * Mirrors the same shape as the messaging store: `unreadCount` is kept on the
 * client so the header bell can read it without subscribing to the TanStack
 * query cache. The actual source of truth is the server — this store is
 * updated by `useUnreadNotificationsCountQuery` and by Echo callbacks when a
 * `notification.created` event fires.
 *
 * Intentionally non-persistent: counts are recomputed on every session.
 */
import { create } from 'zustand';

export interface NotificationsState {
  unreadCount: number;
  setUnreadCount: (n: number) => void;
  incrementUnreadCount: () => void;
  reset: () => void;
}

export const useNotificationsStore = create<NotificationsState>((set) => ({
  unreadCount: 0,
  setUnreadCount: (n) => set({ unreadCount: n }),
  incrementUnreadCount: () =>
    set((state) => ({ unreadCount: state.unreadCount + 1 })),
  reset: () => set({ unreadCount: 0 }),
}));

/** Non-reactive accessor for use outside React (e.g. Echo callbacks). */
export function setNotificationsUnreadCountNonReactive(n: number): void {
  useNotificationsStore.getState().setUnreadCount(n);
}
