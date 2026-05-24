'use client';

/**
 * Search results client island.
 *
 * - Query string is the single source of truth (nuqs `useQueryStates`).
 * - The sidebar + sort dropdown patch the URL; the URL feeds the search query.
 * - Reuses `AdGrid` for the result list so the visual matches the home feed.
 * - The "Save search" button captures the current URL bag so it can be
 *   restored later from /account/saved-searches.
 */
import { useMemo } from 'react';
import Link from 'next/link';
import {
  useQueryStates,
  parseAsString,
  parseAsInteger,
  parseAsStringEnum,
} from 'nuqs';
import { ChevronLeft, ChevronRight, SlidersHorizontalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AdGrid } from '@/components/ads/AdGrid';
import {
  FilterSidebar,
  type FilterValues,
} from '@/components/search/FilterSidebar';
import { SortDropdown } from '@/components/search/SortDropdown';
import { SaveSearchButton } from '@/components/search/SaveSearchButton';
import { useSearchQuery } from '@/lib/queries/search';
import { useCategoryTreeQuery } from '@/lib/queries/categories';
import { useQatarLocationsQuery } from '@/lib/queries/locations';
import { findCategoryBySlug } from '@/store/categories';
import { findLocationBySlug } from '@/store/locations';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { ApiClientError } from '@/lib/api/auth';
import type {
  AdCondition,
  SearchQueryParams,
  SortMode,
} from '@/lib/api/types';

const PER_PAGE = 24;

const CONDITION_VALUES = ['new', 'like_new', 'used'] as const;
const SORT_VALUES: SortMode[] = ['latest', 'oldest', 'price_asc', 'price_desc'];

export function SearchClient() {
  const [urlState, setUrlState] = useQueryStates(
    {
      q: parseAsString.withDefault(''),
      category_slug: parseAsString,
      location_slug: parseAsString,
      price_min: parseAsInteger,
      price_max: parseAsInteger,
      condition: parseAsStringEnum<AdCondition>([...CONDITION_VALUES]),
      sort: parseAsStringEnum<SortMode>(SORT_VALUES).withDefault('latest'),
      page: parseAsInteger.withDefault(1),
    },
    {
      history: 'push',
      shallow: false,
    },
  );

  const { data: categoryTree } = useCategoryTreeQuery();
  const { data: locationTree } = useQatarLocationsQuery();

  const categoryId = useMemo(() => {
    if (!urlState.category_slug || !categoryTree) return undefined;
    return findCategoryBySlug(categoryTree, urlState.category_slug)?.id;
  }, [categoryTree, urlState.category_slug]);

  const locationId = useMemo(() => {
    if (!urlState.location_slug || !locationTree) return undefined;
    return findLocationBySlug(locationTree, urlState.location_slug)?.id;
  }, [locationTree, urlState.location_slug]);

  // Build the API params bag from the URL state — only attach non-empty entries.
  const apiParams: SearchQueryParams = useMemo(() => {
    const params: SearchQueryParams = {
      sort: urlState.sort,
      page: urlState.page,
      per_page: PER_PAGE,
    };
    if (urlState.q) params.q = urlState.q;
    if (urlState.category_slug) {
      params.category_slug = urlState.category_slug;
      if (categoryId) params.category_id = categoryId;
    }
    if (urlState.location_slug) {
      params.location_slug = urlState.location_slug;
      if (locationId) params.location_id = locationId;
    }
    if (urlState.price_min !== null) params.price_min = urlState.price_min;
    if (urlState.price_max !== null) params.price_max = urlState.price_max;
    if (urlState.condition) params.condition = urlState.condition;
    return params;
  }, [urlState, categoryId, locationId]);

  const { data, isLoading, isFetching, isError, error } =
    useSearchQuery(apiParams);

  const filterValues: FilterValues = {
    category_slug: urlState.category_slug,
    location_slug: urlState.location_slug,
    price_min: urlState.price_min,
    price_max: urlState.price_max,
    condition: urlState.condition,
  };

  const handleFilterPatch = (patch: Partial<FilterValues>) => {
    // Reset the page whenever any filter changes — otherwise we land on a
    // page-3 that no longer exists with the new filter set.
    setUrlState({ ...patch, page: 1 });
  };

  const handleClearAll = () => {
    setUrlState({
      category_slug: null,
      location_slug: null,
      price_min: null,
      price_max: null,
      condition: null,
      page: 1,
    });
  };

  const handleSort = (next: SortMode) => {
    setUrlState({ sort: next, page: 1 });
  };

  const handlePage = (next: number) => {
    setUrlState({ page: next });
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const total = data?.meta.total ?? 0;
  const lastPage = data?.meta.last_page ?? 1;

  const headline = urlState.q
    ? t('search.title_for', { query: urlState.q })
    : t('search.title_all', 'كل الإعلانات');

  return (
    <div className="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
      <header className="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div className="min-w-0">
          <p className="text-coral text-xs font-bold uppercase tracking-[0.18em]">
            {t('search.title', 'نتائج البحث')}
          </p>
          <h1 className="font-display text-ink-900 mt-1 truncate text-3xl md:text-4xl">
            {headline}
          </h1>
          {data ? (
            <p className="text-ink-500 mt-1 text-sm">
              {t('search.results_count', { count: String(total) })}
            </p>
          ) : null}
        </div>
        <div className="flex flex-wrap items-center justify-end gap-2">
          <SortDropdown value={urlState.sort} onChange={handleSort} />
          <SaveSearchButton params={apiParams} />
        </div>
      </header>

      <div className="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside className="lg:sticky lg:top-6 lg:self-start">
          <details className="lg:open:hidden border-ink-200 bg-card mb-3 rounded-xl border lg:hidden">
            <summary className="text-ink-700 flex cursor-pointer items-center gap-2 px-4 py-3 text-sm font-medium">
              <SlidersHorizontalIcon className="size-4" aria-hidden />
              {t('common.filters', 'الفلاتر')}
            </summary>
            <div className="px-4 pb-4">
              <FilterSidebar
                values={filterValues}
                onChange={handleFilterPatch}
                facets={data?.facets ?? null}
                onClearAll={handleClearAll}
              />
            </div>
          </details>
          <div className="hidden lg:block">
            <FilterSidebar
              values={filterValues}
              onChange={handleFilterPatch}
              facets={data?.facets ?? null}
              onClearAll={handleClearAll}
            />
          </div>
        </aside>

        <section className="min-w-0">
          {isLoading ? (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {Array.from({ length: 8 }).map((_, index) => (
                <div
                  key={index}
                  className="bg-cream-200 h-64 animate-pulse rounded-xl"
                />
              ))}
            </div>
          ) : isError ? (
            <p className="text-destructive py-12 text-center text-sm">
              {error instanceof ApiClientError
                ? translateMaybeKey(`search.errors.${error.code.toLowerCase()}`) ||
                  translateMaybeKey('search.errors.load_failed') ||
                  error.message
                : t('search.errors.load_failed', 'تعذّر تحميل نتائج البحث')}
            </p>
          ) : !data || data.data.length === 0 ? (
            <EmptyState onReset={handleClearAll} />
          ) : (
            <>
              <div
                className={
                  isFetching
                    ? 'opacity-70 transition-opacity'
                    : 'transition-opacity'
                }
              >
                <AdGrid ads={data.data} />
              </div>
              {lastPage > 1 ? (
                <nav className="mt-8 flex items-center justify-between">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => handlePage(urlState.page - 1)}
                    disabled={urlState.page <= 1}
                  >
                    <ChevronRight className="size-4" aria-hidden />
                    {t('search.prev', 'السابق')}
                  </Button>
                  <span className="text-ink-500 text-sm">
                    {t('search.page_of', {
                      current: String(urlState.page),
                      total: String(lastPage),
                    })}
                  </span>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => handlePage(urlState.page + 1)}
                    disabled={urlState.page >= lastPage}
                  >
                    {t('search.next', 'التالي')}
                    <ChevronLeft className="size-4" aria-hidden />
                  </Button>
                </nav>
              ) : null}
            </>
          )}
        </section>
      </div>
    </div>
  );
}

function EmptyState({ onReset }: { onReset: () => void }) {
  return (
    <div className="border-ink-200 bg-cream-50 rounded-xl border border-dashed px-6 py-16 text-center">
      <p className="text-ink-700 text-base">
        {t('search.no_results', 'لم نعثر على نتائج. جرّب تغيير الفلاتر.')}
      </p>
      <div className="mt-5 flex items-center justify-center gap-2">
        <Button
          type="button"
          variant="outline"
          onClick={onReset}
          className="rounded-full"
        >
          {t('search.reset_filters', 'إعادة ضبط الفلاتر')}
        </Button>
        <Button
          asChild
          variant="default"
          className="bg-coral hover:bg-coral/90 rounded-full text-white"
        >
          <Link href="/ads">{t('home.hero.cta_browse', 'تصفّح الإعلانات')}</Link>
        </Button>
      </div>
    </div>
  );
}
