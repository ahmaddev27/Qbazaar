'use client';

/**
 * Two-input price range filter with a "popular ranges" hint underneath.
 *
 * The numbers are kept in their own local state so the user can keep typing
 * without firing a refetch on every keystroke. The committed value bubbles up
 * `onBlur` (and on Enter) — that's when the URL gets rewritten.
 */
import { useEffect, useState } from 'react';
import { t } from '@/lib/i18n/messages';

interface Props {
  min: number | null;
  max: number | null;
  onChange: (next: { price_min?: number | null; price_max?: number | null }) => void;
  buckets: { range: string; count: number }[] | null;
}

function parseNumber(raw: string): number | null {
  if (raw === '') return null;
  const n = Number(raw);
  return Number.isFinite(n) ? n : null;
}

export function PriceRangeFilter({ min, max, onChange, buckets }: Props) {
  const [localMin, setLocalMin] = useState(min === null ? '' : String(min));
  const [localMax, setLocalMax] = useState(max === null ? '' : String(max));

  // Sync inwards when the URL changes externally (e.g. "Clear all").
  useEffect(() => {
    setLocalMin(min === null ? '' : String(min));
  }, [min]);
  useEffect(() => {
    setLocalMax(max === null ? '' : String(max));
  }, [max]);

  const commitMin = () => {
    const next = parseNumber(localMin);
    if (next !== min) onChange({ price_min: next });
  };
  const commitMax = () => {
    const next = parseNumber(localMax);
    if (next !== max) onChange({ price_max: next });
  };

  const inputClass =
    'border-input bg-card text-ink-900 focus-visible:ring-ring/50 focus-visible:border-ring h-9 w-full rounded-lg border px-3 text-sm transition-colors outline-none focus-visible:ring-3';

  return (
    <div className="space-y-2">
      <div className="grid grid-cols-2 gap-2">
        <label className="space-y-1">
          <span className="text-ink-700 block text-xs font-medium">
            {t('search.facets.price_min', 'الحد الأدنى')}
          </span>
          <input
            type="number"
            inputMode="numeric"
            value={localMin}
            onChange={(event) => setLocalMin(event.target.value)}
            onBlur={commitMin}
            onKeyDown={(event) => {
              if (event.key === 'Enter') {
                event.preventDefault();
                commitMin();
              }
            }}
            className={inputClass}
            placeholder="0"
          />
        </label>
        <label className="space-y-1">
          <span className="text-ink-700 block text-xs font-medium">
            {t('search.facets.price_max', 'الحد الأعلى')}
          </span>
          <input
            type="number"
            inputMode="numeric"
            value={localMax}
            onChange={(event) => setLocalMax(event.target.value)}
            onBlur={commitMax}
            onKeyDown={(event) => {
              if (event.key === 'Enter') {
                event.preventDefault();
                commitMax();
              }
            }}
            className={inputClass}
            placeholder="∞"
          />
        </label>
      </div>

      {buckets && buckets.length > 0 ? (
        <div>
          <p className="text-ink-500 mb-1.5 text-[10px]">
            {t('search.facets.buckets_hint', 'النطاقات السعرية الشائعة')}
          </p>
          <ul className="flex flex-wrap gap-1.5">
            {buckets.slice(0, 6).map((bucket) => (
              <li key={bucket.range}>
                <span className="bg-cream-200 text-ink-700 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px]">
                  <span className="tabular-nums">{bucket.range}</span>
                  <span className="text-ink-500">·</span>
                  <span className="tabular-nums">{bucket.count}</span>
                </span>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  );
}
