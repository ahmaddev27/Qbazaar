'use client';

/**
 * Client island for the categories index. Owns the data fetch and renders
 * either a loading skeleton, an error notice, or the full grid.
 */
import { CategoryGrid } from '@/components/categories/CategoryGrid';
import { useMainCategoriesQuery } from '@/lib/queries/categories';
import { t } from '@/lib/i18n/messages';

export function CategoriesIndexClient() {
  const { data, isLoading, isError, refetch } = useMainCategoriesQuery();

  if (isLoading) {
    return (
      <ul className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <li
            key={i}
            className="bg-cream-200/60 h-[72px] animate-pulse rounded-xl"
            aria-hidden
          />
        ))}
      </ul>
    );
  }

  if (isError || !data) {
    return (
      <div className="border-ink-200 rounded-xl border bg-card p-6 text-center">
        <p className="text-ink-700 text-sm">
          {t('common.error', 'حدث خطأ، حاول مرة أخرى')}
        </p>
        <button
          type="button"
          onClick={() => refetch()}
          className="text-terracotta mt-3 text-sm font-medium hover:underline"
        >
          {t('common.retry', 'إعادة المحاولة')}
        </button>
      </div>
    );
  }

  return <CategoryGrid categories={data} />;
}
