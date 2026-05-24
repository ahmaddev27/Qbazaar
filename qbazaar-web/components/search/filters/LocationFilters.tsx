'use client';

/**
 * Two-level Qatar location picker for the search sidebar.
 *
 * Reuses the existing `LocationPicker` component — it already handles the
 * city/district hierarchy plus a clean "All Qatar" option. This wrapper
 * exists so the search domain can later add facet-count badges next to each
 * city without forking the shared picker.
 */
import { LocationPicker } from '@/components/locations/LocationPicker';

interface Props {
  activeSlug: string | null;
  onChange: (next: string | null) => void;
}

export function LocationFilters({ activeSlug, onChange }: Props) {
  return <LocationPicker value={activeSlug} onChange={onChange} />;
}
