'use client';

/**
 * FE-6.x — Saved searches index.
 *
 * Auth-gated by the wrapping `app/account/layout.tsx`. Lists every saved
 * search the user owns as a card with two actions: Run (route restoration)
 * and Delete (confirm dialog handled inside the card component).
 */
import { Loader2Icon, SearchIcon } from 'lucide-react';
import Link from 'next/link';

import { Button } from '@/components/ui/button';
import { SavedSearchCard } from '@/components/account/SavedSearchCard';
import { useSavedSearchesQuery } from '@/lib/queries/search';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { ApiClientError } from '@/lib/api/auth';

export default function SavedSearchesPage() {
  const { data, isLoading, isError, error } = useSavedSearchesQuery();

  return (
    <div className="space-y-6">
      <header className="space-y-2">
        <p className="text-coral text-xs font-bold uppercase tracking-[0.18em]">
          {t('account.saved_searches.title', 'عمليات البحث المحفوظة')}
        </p>
        <h1 className="font-display text-ink-900 text-3xl md:text-4xl">
          {t('account.saved_searches.title', 'عمليات البحث المحفوظة')}
        </h1>
        <p className="text-ink-500 text-sm">
          {t('account.saved_searches.subtitle')}
        </p>
      </header>

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
            ? translateMaybeKey(`search.errors.${error.code.toLowerCase()}`) ||
              translateMaybeKey('search.errors.load_failed') ||
              error.message
            : t('search.errors.load_failed', 'تعذّر تحميل البيانات')}
        </p>
      ) : !data || data.length === 0 ? (
        <EmptyState />
      ) : (
        <ul className="space-y-3">
          {data.map((search) => (
            <li key={search.id}>
              <SavedSearchCard search={search} />
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function EmptyState() {
  return (
    <div className="border-ink-200 bg-card flex flex-col items-center gap-3 rounded-2xl border border-dashed px-6 py-12 text-center">
      <div className="bg-coral/10 text-coral grid size-12 place-items-center rounded-full">
        <SearchIcon className="size-5" aria-hidden />
      </div>
      <h2 className="font-display text-ink-900 text-xl">
        {t('account.saved_searches.empty_title', 'لا توجد عمليات بحث محفوظة بعد')}
      </h2>
      <p className="text-ink-500 max-w-sm text-sm">
        {t('account.saved_searches.empty_body')}
      </p>
      <Button
        asChild
        className="bg-coral hover:bg-coral/90 mt-2 rounded-full text-white"
      >
        <Link href="/search">{t('home.hero.cta_browse', 'تصفّح الإعلانات')}</Link>
      </Button>
    </div>
  );
}
