'use client';

/**
 * FE-2.1 — Account dashboard.
 *
 * Greeting + 4-cell stat grid sourced from `GET /account/summary`, plus a
 * quick-actions row + a "recent activity" stub. Stats fall back to 0 while
 * the request is in flight so the layout doesn't pop.
 */
import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import {
  MegaphoneIcon,
  MessageCircleIcon,
  FileTextIcon,
  BellIcon,
  PlusIcon,
  ListIcon,
  MailIcon,
  SettingsIcon,
} from 'lucide-react';

import { t } from '@/lib/i18n/messages';
import { useAuth } from '@/hooks/useAuth';
import { getAccountSummary } from '@/lib/api/account';
import type { AccountSummary } from '@/lib/api/types';

const ZERO_SUMMARY: AccountSummary = {
  ads_count: 0,
  drafts_count: 0,
  conversations_count: 0,
  unread_notifications_count: 0,
};

export default function AccountDashboardPage() {
  const { user } = useAuth();
  const { data: summary = ZERO_SUMMARY, isFetching } = useQuery({
    queryKey: ['account', 'summary'],
    queryFn: getAccountSummary,
    // Layout already gated by `useRequireAuth`, but belt-and-braces:
    enabled: Boolean(user),
  });

  const fullName = user?.full_name ?? '';

  return (
    <div className="space-y-8">
      <header className="space-y-1.5">
        <p className="text-coral text-xs font-bold tracking-[0.18em] uppercase">
          {t('account.nav.dashboard')}
        </p>
        <h1 className="font-display text-3xl tracking-tight sm:text-4xl">
          {t('account.dashboard.greeting', { name: fullName })}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('account.dashboard.subtitle')}
        </p>
      </header>

      <section
        aria-busy={isFetching}
        className="grid grid-cols-2 gap-3 sm:grid-cols-4"
      >
        <StatCard
          icon={MegaphoneIcon}
          label={t('account.dashboard.stats.my_ads')}
          value={summary.ads_count}
        />
        <StatCard
          icon={FileTextIcon}
          label={t('account.dashboard.stats.drafts')}
          value={summary.drafts_count}
        />
        <StatCard
          icon={MessageCircleIcon}
          label={t('account.dashboard.stats.conversations')}
          value={summary.conversations_count}
        />
        <StatCard
          icon={BellIcon}
          label={t('account.dashboard.stats.unread_notifications')}
          value={summary.unread_notifications_count}
        />
      </section>

      <section className="space-y-3">
        <h2 className="font-display text-xl tracking-tight">
          {t('account.dashboard.quick_actions.title')}
        </h2>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <QuickAction
            href="/post"
            icon={PlusIcon}
            label={t('account.dashboard.quick_actions.post_ad')}
            primary
          />
          <QuickAction
            href="/account"
            icon={ListIcon}
            label={t('account.dashboard.quick_actions.my_ads')}
          />
          <QuickAction
            href="/messages"
            icon={MailIcon}
            label={t('account.dashboard.quick_actions.messages')}
          />
          <QuickAction
            href="/account/profile"
            icon={SettingsIcon}
            label={t('account.dashboard.quick_actions.settings')}
          />
        </div>
      </section>

      <section className="bg-card ring-foreground/10 space-y-2 rounded-2xl p-5 ring-1">
        <h2 className="font-display text-xl tracking-tight">
          {t('account.dashboard.recent_activity.title')}
        </h2>
        <p className="text-muted-foreground text-sm">
          {t('account.dashboard.recent_activity.coming_soon')}
        </p>
      </section>
    </div>
  );
}

function StatCard({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
  label: string;
  value: number;
}) {
  return (
    <div className="bg-card ring-foreground/10 group flex flex-col gap-2 rounded-2xl p-4 ring-1 transition-shadow hover:shadow-sm">
      <span className="bg-coral/10 text-coral inline-flex size-9 items-center justify-center rounded-xl">
        <Icon className="size-4" aria-hidden />
      </span>
      <div>
        <div className="font-display text-3xl tracking-tight">{value}</div>
        <div className="text-muted-foreground mt-0.5 text-xs">{label}</div>
      </div>
    </div>
  );
}

function QuickAction({
  href,
  icon: Icon,
  label,
  primary,
}: {
  href: string;
  icon: React.ComponentType<{ className?: string; 'aria-hidden'?: boolean }>;
  label: string;
  primary?: boolean;
}) {
  return (
    <Link
      href={href}
      className={
        primary
          ? 'bg-coral text-primary-foreground hover:bg-coral/90 flex items-center justify-center gap-2 rounded-2xl p-4 text-sm font-semibold transition-colors'
          : 'bg-card ring-foreground/10 text-ink-900 hover:bg-muted flex items-center justify-center gap-2 rounded-2xl p-4 text-sm font-semibold ring-1 transition-colors'
      }
    >
      <Icon className="size-4" aria-hidden />
      <span>{label}</span>
    </Link>
  );
}
