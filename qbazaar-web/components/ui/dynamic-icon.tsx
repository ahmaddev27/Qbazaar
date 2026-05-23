'use client';

/**
 * Resolves a lucide-react icon by string name at render time.
 *
 * Backend payloads (categories, etc.) reference icons as plain strings like
 * `"Car"`. We can't statically import them, so we read the icon off the
 * lucide-react module and fall back to `Layers` when the name is unknown.
 *
 * This component is tiny but it lives in `components/ui` because every
 * categories surface uses it.
 */
import * as Lucide from 'lucide-react';
import { Layers, type LucideProps } from 'lucide-react';

interface Props extends Omit<LucideProps, 'name'> {
  /** Lucide-react icon name resolved at render time (e.g. "Car", "Home"). */
  name: string | null | undefined;
}

const ICON_REGISTRY = Lucide as unknown as Record<
  string,
  React.ComponentType<LucideProps>
>;

export function DynamicIcon({ name, ...rest }: Props) {
  if (!name) return <Layers {...rest} />;
  const Comp = ICON_REGISTRY[name];
  if (!Comp) return <Layers {...rest} />;
  return <Comp {...rest} />;
}
