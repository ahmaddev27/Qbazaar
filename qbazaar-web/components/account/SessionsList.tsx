'use client';

/**
 * SessionsList — renders the authenticated user's active sessions.
 *
 * The current session is highlighted with a coral badge. Each row exposes a
 * "Sign out" button calling `DELETE /account/sessions/{id}`; on success the
 * row is removed via cache invalidation.
 */
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Loader2Icon, MonitorSmartphoneIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { formatRelativeTime } from '@/lib/utils';
import { revokeSession } from '@/lib/api/account';
import { ApiClientError } from '@/lib/api/auth';
import type { UserSession } from '@/lib/api/types';

export interface SessionsListProps {
  sessions: UserSession[];
}

export function SessionsList({ sessions }: SessionsListProps) {
  if (sessions.length === 0) {
    return (
      <div className="text-muted-foreground py-10 text-center">
        <p className="font-display text-ink-900 text-lg">
          {t('account.sessions.empty_title')}
        </p>
        <p className="mt-1 text-sm">{t('account.sessions.empty_body')}</p>
      </div>
    );
  }

  return (
    <ul className="divide-border divide-y">
      {sessions.map((session) => (
        <SessionRow key={session.id} session={session} />
      ))}
    </ul>
  );
}

function SessionRow({ session }: { session: UserSession }) {
  const queryClient = useQueryClient();
  const [pendingId, setPendingId] = useState<string | null>(null);

  const mutation = useMutation({
    mutationFn: (id: string) => revokeSession(id),
    onSuccess: () => {
      toast.success(t('account.sessions.revoke_success'));
      queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
    },
    onError: (err) => {
      if (err instanceof ApiClientError) {
        toast.error(
          translateMaybeKey(`account.errors.${err.code}`) ||
            translateMaybeKey(`auth.errors.${err.code}`) ||
            err.message,
        );
      } else {
        toast.error(t('auth.errors.unknown'));
      }
    },
    onSettled: () => setPendingId(null),
  });

  const handleRevoke = () => {
    setPendingId(session.id);
    mutation.mutate(session.id);
  };

  const isPending = pendingId === session.id;
  const deviceLabel =
    session.device_label?.trim() || t('account.sessions.unknown_device');

  return (
    <li className="flex items-center gap-3 py-3">
      <span className="bg-muted text-ink-700 inline-flex size-10 shrink-0 items-center justify-center rounded-xl">
        <MonitorSmartphoneIcon className="size-5" aria-hidden />
      </span>

      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <span className="text-ink-900 truncate text-sm font-semibold">
            {deviceLabel}
          </span>
          {session.is_current ? (
            <Badge className="bg-coral/15 text-coral border-coral/20 border">
              {t('account.sessions.current_badge')}
            </Badge>
          ) : null}
        </div>
        <div className="text-muted-foreground mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs">
          {session.ip_address ? (
            <span>
              <span className="text-ink-500 font-semibold uppercase">
                {t('account.sessions.ip_label')}
              </span>{' '}
              <span dir="ltr">{session.ip_address}</span>
            </span>
          ) : null}
          <span>
            {t('account.sessions.last_used', {
              when: formatRelativeTime(session.last_used_at),
            })}
          </span>
        </div>
      </div>

      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={handleRevoke}
        disabled={isPending || session.is_current}
        className="rounded-full px-3 text-xs font-semibold"
      >
        {isPending ? (
          <>
            <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
            {t('account.sessions.revoking')}
          </>
        ) : (
          t('account.sessions.revoke')
        )}
      </Button>
    </li>
  );
}
