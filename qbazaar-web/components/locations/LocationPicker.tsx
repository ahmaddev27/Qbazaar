'use client';

/**
 * Two-level Qatar location picker.
 *
 * The full tree is loaded once via `useQatarLocationsQuery` (24h cache).
 * Top-level entries are cities, their children are districts. We render two
 * stacked native selects (cleaner UX than nesting in a single dropdown when
 * the user is keyboard-driven) plus an "All Qatar" option that resolves to
 * `null`.
 *
 * The component is fully controlled by `{ value, onChange }`. Value is the
 * slug of the currently selected city OR district (whichever is more
 * specific); `null` means no filter.
 */
import { useMemo } from 'react';
import { useQatarLocationsQuery } from '@/lib/queries/locations';
import { findLocationBySlug } from '@/store/locations';
import { localized, getLocale } from '@/lib/i18n/locale';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import type { Location } from '@/lib/api/types';

interface Props {
  value: string | null;
  onChange: (slug: string | null) => void;
  className?: string;
}

const ALL = '__all__';

export function LocationPicker({ value, onChange, className }: Props) {
  const locale = getLocale();
  const { data, isLoading, isError } = useQatarLocationsQuery();

  const cities: Location[] = useMemo(() => data ?? [], [data]);

  // Resolve the currently selected city (whether the user picked the city
  // itself or one of its district children).
  const { cityNode, districtSlug } = useMemo(() => {
    if (!value || !data) return { cityNode: null, districtSlug: null };
    for (const city of data) {
      if (city.slug === value) return { cityNode: city, districtSlug: null };
      const child = findLocationBySlug(city.children, value);
      if (child) return { cityNode: city, districtSlug: child.slug };
    }
    return { cityNode: null, districtSlug: null };
  }, [data, value]);

  const handleCityChange = (slug: string) => {
    if (slug === ALL) {
      onChange(null);
      return;
    }
    onChange(slug);
  };

  const handleDistrictChange = (slug: string) => {
    if (slug === ALL) {
      // Reset to the parent city (broader scope).
      onChange(cityNode?.slug ?? null);
      return;
    }
    onChange(slug);
  };

  if (isLoading) {
    return (
      <div
        className={cn(
          'text-ink-500 h-9 animate-pulse rounded-lg bg-cream-200 px-3 text-sm',
          className,
        )}
        aria-busy="true"
      />
    );
  }

  if (isError) {
    return (
      <p className={cn('text-destructive text-sm', className)}>
        {t('locations.errors.not_found', 'تعذّر تحميل المواقع')}
      </p>
    );
  }

  const selectClass =
    'border-input bg-card text-ink-900 focus-visible:ring-ring/50 focus-visible:border-ring h-9 w-full rounded-lg border px-3 text-sm transition-colors outline-none focus-visible:ring-3';

  return (
    <div className={cn('space-y-2', className)}>
      <div>
        <label className="text-ink-700 mb-1 block text-xs font-medium">
          {t('locations.city', 'المدينة')}
        </label>
        <select
          value={cityNode?.slug ?? ALL}
          onChange={(e) => handleCityChange(e.target.value)}
          className={selectClass}
        >
          <option value={ALL}>{t('locations.all', 'كل قطر')}</option>
          {cities.map((city) => (
            <option key={city.id} value={city.slug}>
              {localized(city.name, locale)}
            </option>
          ))}
        </select>
      </div>

      {cityNode && cityNode.children.length > 0 ? (
        <div>
          <label className="text-ink-700 mb-1 block text-xs font-medium">
            {t('locations.district', 'المنطقة')}
          </label>
          <select
            value={districtSlug ?? ALL}
            onChange={(e) => handleDistrictChange(e.target.value)}
            className={selectClass}
          >
            <option value={ALL}>{t('locations.all', 'كل المدينة')}</option>
            {cityNode.children.map((district) => (
              <option key={district.id} value={district.slug}>
                {localized(district.name, locale)}
              </option>
            ))}
          </select>
        </div>
      ) : null}
    </div>
  );
}
