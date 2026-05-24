'use client';

/**
 * Category list with facet counts.
 *
 * Renders flat top-level categories (no recursive nesting) for the search
 * sidebar — the search page already exposes the full tree through the
 * dedicated /categories explorer. Each row links the slug to the facet
 * count returned by the backend.
 */
import { useMemo } from 'react';
import { useCategoryTreeQuery } from '@/lib/queries/categories';
import { localized, getLocale } from '@/lib/i18n/locale';
import { FacetCountChip } from '../FacetCountChip';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n/messages';

interface Props {
  activeSlug: string | null;
  onChange: (next: string | null) => void;
  counts: Record<string, number> | null;
}

export function CategoryFilters({ activeSlug, onChange, counts }: Props) {
  const locale = getLocale();
  const { data, isLoading } = useCategoryTreeQuery();

  // Sort categories by facet count (descending) so the most-populated rows
  // float to the top while keeping the API-returned `order` as the tiebreak.
  const items = useMemo(() => {
    if (!data) return [];
    return [...data].sort((a, b) => {
      const aCount = counts?.[a.slug] ?? -1;
      const bCount = counts?.[b.slug] ?? -1;
      if (aCount === bCount) return a.order - b.order;
      return bCount - aCount;
    });
  }, [data, counts]);

  if (isLoading) {
    return <div className="bg-cream-200 h-32 animate-pulse rounded-lg" />;
  }

  if (items.length === 0) {
    return (
      <p className="text-ink-500 text-xs">
        {t('categories.no_subcategories', 'لا توجد أقسام بعد')}
      </p>
    );
  }

  return (
    <ul className="space-y-0.5">
      <li>
        <FilterRow
          active={!activeSlug}
          label={t('categories.all', 'الأقسام')}
          onClick={() => onChange(null)}
        />
      </li>
      {items.map((cat) => {
        const count = counts?.[cat.slug];
        return (
          <li key={cat.id}>
            <FilterRow
              active={activeSlug === cat.slug}
              label={localized(cat.name, locale)}
              count={count}
              onClick={() =>
                onChange(activeSlug === cat.slug ? null : cat.slug)
              }
            />
          </li>
        );
      })}
    </ul>
  );
}

interface RowProps {
  active: boolean;
  label: string;
  count?: number;
  onClick: () => void;
}

function FilterRow({ active, label, count, onClick }: RowProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm transition-colors',
        active
          ? 'bg-coral/10 text-terracotta font-medium'
          : 'text-ink-700 hover:bg-cream-200',
      )}
    >
      <span className="truncate">{label}</span>
      {typeof count === 'number' ? (
        <FacetCountChip count={count} active={active} />
      ) : null}
    </button>
  );
}
