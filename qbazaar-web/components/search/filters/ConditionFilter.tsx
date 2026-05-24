'use client';

/**
 * Pill-group selector for the three ad conditions.
 *
 * Clicking the active pill clears the filter (toggle behaviour) — matches the
 * pattern used by the categories list in the same sidebar.
 */
import { FacetCountChip } from '../FacetCountChip';
import { cn } from '@/lib/utils';
import { t } from '@/lib/i18n/messages';
import type { AdCondition } from '@/lib/api/types';

interface Props {
  value: AdCondition | null;
  onChange: (next: AdCondition | null) => void;
  counts: Record<AdCondition, number> | null;
}

const OPTIONS: AdCondition[] = ['new', 'like_new', 'used'];

export function ConditionFilter({ value, onChange, counts }: Props) {
  return (
    <div className="flex flex-wrap gap-1.5">
      {OPTIONS.map((option) => {
        const active = value === option;
        const count = counts?.[option];
        return (
          <button
            key={option}
            type="button"
            onClick={() => onChange(active ? null : option)}
            aria-pressed={active}
            className={cn(
              'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors',
              active
                ? 'bg-coral border-coral text-white'
                : 'border-ink-200 bg-card text-ink-700 hover:bg-cream-200',
            )}
          >
            <span>{t(`ads.condition.${option}`)}</span>
            {typeof count === 'number' ? (
              <FacetCountChip count={count} active={active} />
            ) : null}
          </button>
        );
      })}
    </div>
  );
}
