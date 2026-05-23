/**
 * Locations store — Zustand.
 *
 * Mirrors the categories store: holds the current Qatar location tree so
 * pickers + filters can do `findBySlug` without re-fetching. The tree is
 * already cached by TanStack Query for 24h.
 */
import { create } from 'zustand';
import type { Location } from '@/lib/api/types';

/**
 * Recursive DFS through the Qatar location forest.
 */
export function findLocationBySlug(
  nodes: Location[] | null | undefined,
  slug: string,
): Location | null {
  if (!nodes) return null;
  for (const node of nodes) {
    if (node.slug === slug) return node;
    const hit = findLocationBySlug(node.children, slug);
    if (hit) return hit;
  }
  return null;
}

export interface LocationsState {
  qatar: Location[] | null;
  hydrated: boolean;
  setQatar: (qatar: Location[]) => void;
  findBySlug: (slug: string) => Location | null;
}

export const useLocationsStore = create<LocationsState>((set, get) => ({
  qatar: null,
  hydrated: false,
  setQatar: (qatar) => set({ qatar, hydrated: true }),
  findBySlug: (slug) => findLocationBySlug(get().qatar, slug),
}));
