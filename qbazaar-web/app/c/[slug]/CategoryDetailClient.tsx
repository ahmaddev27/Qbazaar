'use client';

/**
 * Client island for the category detail page.
 *
 * Responsibilities:
 *  1. Pull the full category tree (so we can render the breadcrumb +
 *     resolve the active node + display children).
 *  2. Pull per-category filters/fields/stats — driven off the slug.
 *  3. Render the layout: breadcrumb → header → empty ads card → children
 *     grid → filters sidebar.
 *  4. Call `notFound()` once the tree resolves and the slug is absent.
 */
import { useMemo, useState } from 'react';
import { notFound } from 'next/navigation';
import { CategoryGrid } from '@/components/categories/CategoryGrid';
import { CategoryBreadcrumb } from '@/components/categories/CategoryBreadcrumb';
import {
  CategoryFilters,
  type FilterValues,
} from '@/components/categories/CategoryFilters';
import { LocationPicker } from '@/components/locations/LocationPicker';
import {
  useCategoryFiltersQuery,
  useCategoryStatsQuery,
  useCategoryTreeQuery,
} from '@/lib/queries/categories';
import { findCategoryBySlug } from '@/store/categories';
import { localized, getLocale } from '@/lib/i18n/locale';
import { t } from '@/lib/i18n/messages';
import type { CategoryNode } from '@/lib/api/types';

interface Props {
  slug: string;
}

export function CategoryDetailClient({ slug }: Props) {
  const locale = getLocale();
  const treeQuery = useCategoryTreeQuery();
  const filtersQuery = useCategoryFiltersQuery(slug);
  const statsQuery = useCategoryStatsQuery(slug);

  const node: CategoryNode | null = useMemo(
    () => findCategoryBySlug(treeQuery.data ?? null, slug),
    [treeQuery.data, slug],
  );

  const [filterValues, setFilterValues] = useState<FilterValues>({});
  const [locationSlug, setLocationSlug] = useState<string | null>(null);

  if (treeQuery.isLoading) {
    return <DetailSkeleton />;
  }

  // Tree resolved but slug missing → real 404.
  if (treeQuery.data && !node) {
    notFound();
  }

  if (treeQuery.isError || !treeQuery.data) {
    return (
      <main className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <p className="text-destructive text-sm">
          {t('categories.errors.not_found', 'لم نعثر على هذا القسم')}
        </p>
      </main>
    );
  }

  // After the guards above, node is guaranteed non-null.
  const category = node!;
  const subAdsCount =
    statsQuery.data?.sub_ads_count ?? statsQuery.data?.ads_count ?? null;

  return (
    <main className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
      <CategoryBreadcrumb slug={slug} tree={treeQuery.data} className="mb-6" />

      <header className="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="font-display text-4xl leading-[1.05] tracking-tight md:text-6xl">
            <em className="not-italic md:italic">
              {localized(category.name, locale)}
            </em>
          </h1>
          {category.description ? (
            <p className="text-ink-700 mt-3 max-w-xl text-sm leading-relaxed">
              {localized(category.description, locale)}
            </p>
          ) : null}
        </div>
        {subAdsCount !== null ? (
          <span className="bg-coral/15 text-terracotta inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
            {t(
              'categories.ads_count',
              { count: formatNumber(subAdsCount, locale) },
              `${formatNumber(subAdsCount, locale)} إعلان`,
            )}
          </span>
        ) : null}
      </header>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_280px]">
        <section className="space-y-8">
          <EmptyAdsCard />

          {category.children.length > 0 ? (
            <section aria-labelledby="subcats-heading">
              <h2
                id="subcats-heading"
                className="text-ink-900 mb-3 text-base font-medium"
              >
                {t('categories.subcategories', 'الأقسام الفرعية')}
              </h2>
              <CategoryGrid categories={category.children} />
            </section>
          ) : null}
        </section>

        <aside className="space-y-6">
          <section aria-labelledby="location-heading">
            <h2
              id="location-heading"
              className="text-ink-900 mb-3 text-base font-medium"
            >
              {t('locations.pick', 'الموقع')}
            </h2>
            <LocationPicker
              value={locationSlug}
              onChange={setLocationSlug}
            />
          </section>

          <section aria-labelledby="filters-heading">
            <h2
              id="filters-heading"
              className="text-ink-900 mb-3 text-base font-medium"
            >
              {t('common.filters', 'الفلاتر')}
            </h2>
            {filtersQuery.isLoading ? (
              <div className="space-y-3">
                {Array.from({ length: 3 }).map((_, i) => (
                  <div
                    key={i}
                    className="bg-cream-200/60 h-9 animate-pulse rounded-lg"
                    aria-hidden
                  />
                ))}
              </div>
            ) : (
              <CategoryFilters
                filters={filtersQuery.data ?? []}
                values={filterValues}
                onChange={setFilterValues}
              />
            )}
          </section>
        </aside>
      </div>
    </main>
  );
}

function EmptyAdsCard() {
  return (
    <div className="border-sage/30 bg-sage/5 rounded-2xl border p-8 text-center">
      <p className="text-sage font-display text-2xl tracking-tight">
        {t('categories.ads_coming_soon', 'الإعلانات قادمة قريباً')}
      </p>
      <p className="text-ink-700 mx-auto mt-2 max-w-md text-sm leading-relaxed">
        {t(
          'categories.ads_coming_soon_subtitle',
          'نعمل على إطلاق صفحة الإعلانات. تابعنا — هي قادمة في القريب العاجل.',
        )}
      </p>
    </div>
  );
}

function DetailSkeleton() {
  return (
    <main className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
      <div className="bg-cream-200/60 mb-6 h-5 w-48 animate-pulse rounded" />
      <div className="bg-cream-200/60 mb-8 h-12 w-72 animate-pulse rounded" />
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_280px]">
        <div className="bg-cream-200/60 h-48 animate-pulse rounded-2xl" />
        <div className="space-y-3">
          {Array.from({ length: 4 }).map((_, i) => (
            <div
              key={i}
              className="bg-cream-200/60 h-9 animate-pulse rounded-lg"
            />
          ))}
        </div>
      </div>
    </main>
  );
}

function formatNumber(count: number, locale: 'ar' | 'en'): string {
  const lang = locale === 'ar' ? 'ar-EG' : 'en-US';
  return new Intl.NumberFormat(lang).format(count);
}
