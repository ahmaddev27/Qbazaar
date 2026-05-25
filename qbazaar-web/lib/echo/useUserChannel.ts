'use client';

/**
 * Subscribe to the private `user.{id}` channel so the badge + inbox previews
 * stay live across the whole app.
 *
 * Events handled:
 *  - `message.sent`         — a new message landed in a conversation I'm in
 *  - `conversation.read`    — someone else read messages, refresh unread count
 *  - `notification.created` — a new in-app notification was created (Sprint 10)
 *
 * Strategy is to invalidate the relevant TanStack queries on every event;
 * the existing query hooks then re-fetch their own minimal payloads. This
 * keeps the socket layer dumb and reuses every caching/staleness rule we
 * already wrote for REST. Callers can pass an `onNotification` callback to
 * surface an inline toast when a notification arrives.
 */
import { useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import {
  appendIncomingMessageToCache,
  messagingKeys,
} from '@/lib/queries/messaging';
import { notificationsKeys } from '@/lib/queries/notifications';
import { useNotificationsStore } from '@/store/notifications';
import { getEcho } from './client';
import type { Message } from '@/lib/api/types';

interface MessageSentPayload {
  message: Message;
}

interface ConversationReadPayload {
  conversation_id: string;
  read_at: string;
  reader_id: string;
}

/**
 * Lean shape pushed on `notification.created`. Mirrors the BE-10 broadcast
 * payload — a strict subset of the full `Notification` resource (enough to
 * render a toast without an extra round-trip).
 */
export interface NotificationCreatedPayload {
  id: string;
  type: string;
  category: string;
  title: string;
  body: string;
  icon: string | null;
  cta_url: string | null;
  created_at: string;
}

interface UseUserChannelOptions {
  /** Fired when a `notification.created` event arrives (e.g. to toast). */
  onNotification?: (payload: NotificationCreatedPayload) => void;
}

export function useUserChannel(
  userId: string | null | undefined,
  options: UseUserChannelOptions = {},
): void {
  const qc = useQueryClient();
  const optsRef = useRef(options);
  // Keep the latest callback without retriggering the subscription effect —
  // re-subscribing on every render would thrash the WebSocket connection.
  optsRef.current = options;

  useEffect(() => {
    if (!userId) return;
    let cancelled = false;
    let cleanup: (() => void) | null = null;

    (async () => {
      const echo = await getEcho();
      if (!echo || cancelled) return;

      const channelName = `user.${userId}`;
      const channel = echo.private(channelName);

      const onMessageSent = (payload: unknown) => {
        const p = payload as MessageSentPayload | Message;
        // Backend may emit the raw Message OR wrap it in `{ message }`.
        const message = 'message' in p ? p.message : (p as Message);
        if (!message?.conversation_id) return;
        appendIncomingMessageToCache(qc, message);
        qc.invalidateQueries({ queryKey: messagingKeys.lists() });
        qc.invalidateQueries({ queryKey: messagingKeys.unread() });
      };

      const onConversationRead = (payload: unknown) => {
        const p = payload as ConversationReadPayload;
        if (!p?.conversation_id) return;
        qc.invalidateQueries({ queryKey: messagingKeys.unread() });
        qc.invalidateQueries({ queryKey: messagingKeys.lists() });
        qc.invalidateQueries({
          queryKey: messagingKeys.messages(p.conversation_id),
        });
      };

      const onNotificationCreated = (payload: unknown) => {
        const p = payload as NotificationCreatedPayload;
        if (!p?.id) return;
        // Optimistically bump the bell so the UI feels instant; the next
        // `unread-count` refetch reconciles with the server.
        useNotificationsStore.getState().incrementUnreadCount();
        qc.invalidateQueries({ queryKey: notificationsKeys.lists() });
        qc.invalidateQueries({ queryKey: notificationsKeys.unread() });
        optsRef.current.onNotification?.(p);
      };

      channel.listen('.message.sent', onMessageSent);
      channel.listen('message.sent', onMessageSent);
      channel.listen('.conversation.read', onConversationRead);
      channel.listen('conversation.read', onConversationRead);
      channel.listen('.notification.created', onNotificationCreated);
      channel.listen('notification.created', onNotificationCreated);

      cleanup = () => {
        try {
          channel.stopListening('.message.sent');
          channel.stopListening('message.sent');
          channel.stopListening('.conversation.read');
          channel.stopListening('conversation.read');
          channel.stopListening('.notification.created');
          channel.stopListening('notification.created');
          echo.leave(channelName);
        } catch {
          // ignore — socket may already be torn down
        }
      };
    })();

    return () => {
      cancelled = true;
      cleanup?.();
    };
  }, [userId, qc]);
}
