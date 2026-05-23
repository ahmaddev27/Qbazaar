import type { Metadata } from 'next';
import { t } from '@/lib/i18n/messages';
import { CategoriesIndexClient } from './CategoriesIndexClient';

/**
 * Top-level category index — `/categories`.
 *
 * The page shell is a server component that handles SEO + the page header;
 * the actual data fetch + card grid live in a client island so they can
 * use TanStack Query for caching/revalidation across navigations.
 */
export const metadata: Metadata = {
  title: t('categories.all', 'الأقسام'),
};

export default function CategoriesIndexPage() {
  return (
    <main className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
      <header className="mb-8">
        <p className="text-coral text-xs font-bold tracking-[0.18em] uppercase">
          QBazaar
        </p>
        <h1 className="font-display mt-2 text-4xl leading-[1.05] tracking-tight md:text-5xl">
          {t('categories.all', 'الأقسام')}
        </h1>
        <p className="text-ink-700 mt-3 max-w-xl text-sm leading-relaxed">
          {t(
            'categories.index_subtitle',
            'اكتشف ما يبيعه ويشتريه جيرانك في قطر',
          )}
        </p>
      </header>

      <CategoriesIndexClient />
    </main>
  );
}
