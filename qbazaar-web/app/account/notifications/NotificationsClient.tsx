'use client';

/**
 * Notifications index — paginated list with "All / Unread" tabs.
 *
 * URL `?tab=unread|all` is the source of truth so deep links restore the
 * active filter and the browser back-button works. Bulk "Mark all as read"
 * lives in the header and is disabled while a request is in flight.
 */
import { useState } from 'react';
import { parseAsStringEnum, useQueryState } from 'nuqs';
import {
  BellIcon,
  CheckCheckIcon,
  ChevronLeft,
  ChevronRight,
  Loader2Icon,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { NotificationRow } from '@/components/notifications/NotificationRow';
import {
  useMarkAllNotificationsReadMutation,
  useNotificationsQuery,
  useUnreadNotificationsCountQuery,
} from '@/lib/queries/notifications';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { ApiClientError } from '@/lib/api/auth';
import { cn } from '@/lib/utils';

const PER_PAGE = 20;

type Tab = 'all' | 'unread';

export function NotificationsClient() {
  const [tab, setTab] = useQueryState(
    'tab',
    parseAsStringEnum<Tab>(['all', 'unread']).withDefault('all'),
  );
  const [page, setPage] = useState(1);

  const params =
    tab === 'unread'
      ? { page, per_page: PER_PAGE, unread: 1 as const }
      : { page, per_page: PER_PAGE };

  const { data, isLoading, isError, error } = useNotificationsQuery(params);
  const { data: unread } = useUnreadNotificationsCountQuery();
  const markAllRead = useMarkAllNotificationsReadMutation();

  const lastPage = data?.meta.last_page ?? 1;
  const unreadCount = unread?.total ?? 0;
  const items = data?.data ?? [];

  const handleTabChange = (next: Tab) => {
    void setTab(next);
    setPage(1);
  };

  return (
    <div className="space-y-6">
      <header className="space-y-2">
        <p className="text-coral text-xs font-bold uppercase tracking-[0.18em]">
          {t('account.nav.notifications', 'الإشعارات')}
        </p>
        <div className="flex flex-wrap items-end justify-between gap-3">
          <h1 className="font-display text-ink-900 text-3xl md:text-4xl">
            {t('notifications.title', 'الإشعارات')}
          </h1>
          {unreadCount > 0 ? (
            <Button
              type="button"
              variant="outline"
              size="default"
              className="rounded-full"
              disabled={markAllRead.isPending}
              onClick={() => markAllRead.mutate()}
            >
              {markAllRead.isPending ? (
                <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
              ) : (
                <CheckCheckIcon className="size-3.5" aria-hidden />
              )}
              {t('notifications.mark_all_read', 'تعليم الكل كمقروء')}
            </Button>
          ) : null}
        </div>
      </header>

      {/* Tabs */}
      <nav
        role="tablist"
        aria-label={t('notifications.title', 'الإشعارات')}
        className="border-ink-200 bg-card inline-flex rounded-full border p-1"
      >
        <TabButton
          active={tab === 'all'}
          onClick={() => handleTabChange('all')}
          label={t('notifications.tabs.all', 'الكل')}
        />
        <TabButton
          active={tab === 'unread'}
          onClick={() => handleTabChange('unread')}
          label={t('notifications.tabs.unread', 'غير المقروء')}
          badge={unreadCount > 0 ? unreadCount : undefined}
        />
      </nav>

      {/* List */}
      {isLoading ? (
        <div className="flex justify-center py-12" role="status">
          <Loader2Icon
            className="text-muted-foreground size-6 animate-spin"
            aria-hidden
          />
        </div>
      ) : isError ? (
        <p className="text-destructive py-12 text-center text-sm">
          {error instanceof ApiClientError
            ? translateMaybeKey(
                `notifications.errors.${error.code.toLowerCase()}`,
              ) || error.message
            : t('common.error', 'حدث خطأ، حاول مرة أخرى')}
        </p>
      ) : items.length === 0 ? (
        <EmptyState tab={tab} />
      ) : (
        <>
          <ul className="space-y-2.5">
            {items.map((n) => (
              <li key={n.id}>
                <NotificationRow notification={n} />
              </li>
            ))}
          </ul>

          {lastPage > 1 ? (
            <nav className="mt-8 flex items-center justify-between">
              <Button
                type="button"
                variant="outline"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1}
              >
                <ChevronRight className="size-4" />
                {t('ads.list.prev', 'السابق')}
              </Button>
              <span className="text-ink-500 text-sm">
                {t(
                  'ads.list.page_of',
                  { current: String(page), total: String(lastPage) },
                  `${page} / ${lastPage}`,
                )}
              </span>
              <Button
                type="button"
                variant="outline"
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                disabled={page >= lastPage}
              >
                {t('ads.list.next', 'التالي')}
                <ChevronLeft className="size-4" />
              </Button>
            </nav>
          ) : null}
        </>
      )}
    </div>
  );
}

function TabButton({
  active,
  onClick,
  label,
  badge,
}: {
  active: boolean;
  onClick: () => void;
  label: string;
  badge?: number;
}) {
  return (
    <button
      type="button"
      role="tab"
      aria-selected={active}
      onClick={onClick}
      className={cn(
        'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition-colors',
        active
          ? 'bg-coral text-white shadow-sm'
          : 'text-ink-700 hover:bg-cream-200',
      )}
    >
      <span>{label}</span>
      {typeof badge === 'number' ? (
        <span
          className={cn(
            'inline-flex min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-[18px]',
            active ? 'bg-white text-coral' : 'bg-coral text-white',
          )}
        >
          {badge > 99 ? '99+' : badge}
        </span>
      ) : null}
    </button>
  );
}

function EmptyState({ tab }: { tab: Tab }) {
  return (
    <div className="border-ink-200 bg-card flex flex-col items-center gap-3 rounded-2xl border border-dashed px-6 py-12 text-center">
      <div className="bg-coral/10 text-coral grid size-12 place-items-center rounded-full">
        <BellIcon className="size-5" aria-hidden />
      </div>
      <h2 className="font-display text-ink-900 text-xl">
        {tab === 'unread'
          ? t('notifications.empty.unread', 'لا توجد إشعارات غير مقروءة')
          : t('notifications.empty.all', 'لا توجد إشعارات بعد')}
      </h2>
    </div>
  );
}
