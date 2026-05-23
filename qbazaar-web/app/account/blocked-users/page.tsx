'use client';

/**
 * FE-2.10 — Blocked users list.
 *
 * Each row exposes an "Unblock" button calling `DELETE /users/{id}/block`.
 * On success the list is refetched so the row drops out.
 */
import Link from 'next/link';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Loader2Icon } from 'lucide-react';

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { formatRelativeTime } from '@/lib/utils';
import { listBlockedUsers } from '@/lib/api/account';
import { unblockUser } from '@/lib/api/users';
import { ApiClientError } from '@/lib/api/auth';
import type { BlockedUser } from '@/lib/api/types';

export default function BlockedUsersPage() {
  const queryClient = useQueryClient();
  const {
    data: blocked = [],
    isLoading,
    error,
  } = useQuery({
    queryKey: ['account', 'blocked-users'],
    queryFn: listBlockedUsers,
  });

  const mutation = useMutation({
    mutationFn: (userId: string) => unblockUser(userId),
    onSuccess: () => {
      toast.success(t('account.blocked_users.unblock_success'));
      queryClient.invalidateQueries({ queryKey: ['account', 'blocked-users'] });
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
  });

  return (
    <section className="space-y-6">
      <header className="space-y-1.5">
        <h1 className="font-display text-3xl tracking-tight sm:text-4xl">
          {t('account.blocked_users.title')}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('account.blocked_users.subtitle')}
        </p>
      </header>

      <div className="bg-card ring-foreground/10 rounded-2xl ring-1">
        {isLoading ? (
          <div className="flex justify-center py-10" role="status">
            <Loader2Icon
              className="text-muted-foreground size-5 animate-spin"
              aria-hidden
            />
          </div>
        ) : error ? (
          <p className="text-destructive p-4 text-sm" role="alert">
            {t('auth.errors.network')}
          </p>
        ) : blocked.length === 0 ? (
          <div className="text-muted-foreground py-10 text-center">
            <p className="font-display text-ink-900 text-lg">
              {t('account.blocked_users.empty_title')}
            </p>
            <p className="mt-1 text-sm">
              {t('account.blocked_users.empty_body')}
            </p>
          </div>
        ) : (
          <ul className="divide-border divide-y">
            {blocked.map((user: BlockedUser) => (
              <li
                key={user.id}
                className="flex items-center gap-3 p-4 sm:p-5"
              >
                <Link
                  href={`/u/${user.id}`}
                  className="flex min-w-0 flex-1 items-center gap-3"
                >
                  <Avatar size="lg">
                    {user.avatar_url ? (
                      <AvatarImage src={user.avatar_url} alt={user.full_name} />
                    ) : null}
                    <AvatarFallback>
                      {initials(user.full_name)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="min-w-0">
                    <p className="text-ink-900 truncate text-sm font-semibold">
                      {user.full_name}
                    </p>
                    <p className="text-muted-foreground text-xs">
                      {t('account.blocked_users.blocked_at', {
                        when: formatRelativeTime(user.blocked_at),
                      })}
                    </p>
                  </div>
                </Link>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => mutation.mutate(user.id)}
                  disabled={mutation.isPending}
                  className="rounded-full px-3 text-xs font-semibold"
                >
                  {mutation.isPending &&
                  mutation.variables === user.id ? (
                    <>
                      <Loader2Icon
                        className="size-3.5 animate-spin"
                        aria-hidden
                      />
                      {t('account.blocked_users.unblocking')}
                    </>
                  ) : (
                    t('account.blocked_users.unblock')
                  )}
                </Button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  );
}

function initials(fullName: string): string {
  return fullName
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}
