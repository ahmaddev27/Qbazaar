'use client';

/**
 * Category-driven filter sidebar.
 *
 * Renders the per-category custom filters returned by the API. The shape is
 * driven entirely by `filters[i].type`:
 *
 * - `select`  → native <select> (Base-UI select is keyed off `useState`, the
 *   plain native version is enough for the preview surface).
 * - `range`   → two number inputs (min / max) — values stored as
 *   `{ min?: number, max?: number }` inside the parent `values` map.
 * - `boolean` → shadcn Switch.
 *
 * Values + onChange are fully controlled by the parent so the filter state
 * can be hoisted into a URL query string (`nuqs` planned in Sprint 5).
 */
import { Switch } from '@/components/ui/switch';
import { localized, getLocale } from '@/lib/i18n/locale';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import type { CategoryFilter } from '@/lib/api/types';

export type FilterValue =
  | string
  | boolean
  | { min?: number; max?: number }
  | null
  | undefined;

export type FilterValues = Record<string, FilterValue>;

interface Props {
  filters: CategoryFilter[];
  values: FilterValues;
  onChange: (next: FilterValues) => void;
  className?: string;
}

export function CategoryFilters({
  filters,
  values,
  onChange,
  className,
}: Props) {
  const locale = getLocale();

  if (filters.length === 0) {
    return (
      <p className={cn('text-ink-500 py-4 text-center text-sm', className)}>
        {t('categories.no_filters', 'لا توجد فلاتر متاحة')}
      </p>
    );
  }

  const patch = (key: string, value: FilterValue) =>
    onChange({ ...values, [key]: value });

  return (
    <div className={cn('space-y-5', className)}>
      {filters.map((filter) => {
        const label = localized(filter.label, locale);
        const id = `filter-${filter.key}`;
        return (
          <div key={filter.key} className="space-y-1.5">
            <label
              htmlFor={id}
              className="text-ink-700 block text-xs font-medium"
            >
              {label}
            </label>
            {filter.type === 'select' ? (
              <select
                id={id}
                value={(values[filter.key] as string | undefined) ?? ''}
                onChange={(e) => patch(filter.key, e.target.value || null)}
                className="border-input bg-card text-ink-900 focus-visible:ring-ring/50 focus-visible:border-ring h-9 w-full rounded-lg border px-3 text-sm transition-colors outline-none focus-visible:ring-3"
              >
                <option value="">
                  {t('common.or', '—')}
                </option>
                {(filter.options ?? []).map((opt) => (
                  <option key={opt} value={opt}>
                    {opt}
                  </option>
                ))}
              </select>
            ) : null}
            {filter.type === 'range' ? (
              <RangeInputs
                value={
                  (values[filter.key] as
                    | { min?: number; max?: number }
                    | undefined) ?? {}
                }
                onChange={(next) => patch(filter.key, next)}
              />
            ) : null}
            {filter.type === 'boolean' ? (
              <div className="flex items-center gap-2">
                <Switch
                  id={id}
                  checked={Boolean(values[filter.key])}
                  onCheckedChange={(checked) => patch(filter.key, checked)}
                />
                <span className="text-ink-700 text-xs">{label}</span>
              </div>
            ) : null}
          </div>
        );
      })}
    </div>
  );
}

interface RangeProps {
  value: { min?: number; max?: number };
  onChange: (next: { min?: number; max?: number }) => void;
}

function RangeInputs({ value, onChange }: RangeProps) {
  const parse = (raw: string): number | undefined => {
    if (raw === '') return undefined;
    const n = Number(raw);
    return Number.isFinite(n) ? n : undefined;
  };

  return (
    <div className="grid grid-cols-2 gap-2">
      <input
        type="number"
        inputMode="numeric"
        placeholder={t('common.min', 'الأدنى')}
        value={value.min ?? ''}
        onChange={(e) => onChange({ ...value, min: parse(e.target.value) })}
        className="border-input bg-card text-ink-900 focus-visible:ring-ring/50 focus-visible:border-ring h-9 w-full rounded-lg border px-3 text-sm transition-colors outline-none focus-visible:ring-3"
      />
      <input
        type="number"
        inputMode="numeric"
        placeholder={t('common.max', 'الأعلى')}
        value={value.max ?? ''}
        onChange={(e) => onChange({ ...value, max: parse(e.target.value) })}
        className="border-input bg-card text-ink-900 focus-visible:ring-ring/50 focus-visible:border-ring h-9 w-full rounded-lg border px-3 text-sm transition-colors outline-none focus-visible:ring-3"
      />
    </div>
  );
}
