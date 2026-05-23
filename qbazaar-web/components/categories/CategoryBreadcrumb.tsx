/**
 * Server-friendly breadcrumb from root → current category.
 *
 * Walks the category tree to build the chain of ancestors for the given
 * slug. Renders a horizontal list of links separated by chevrons. Uses
 * logical CSS (`ms-*` / `me-*`) so the chevron direction follows the
 * document's `dir` attribute automatically.
 */
import Link from 'next/link';
import { ChevronLeft } from 'lucide-react';
import { localized, getLocale } from '@/lib/i18n/locale';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import type { CategoryNode } from '@/lib/api/types';

interface Props {
  slug: string;
  tree: CategoryNode[];
  className?: string;
}

/**
 * Return the ancestor path (root → leaf inclusive) for a slug, or `null`
 * when the slug isn't in the tree.
 */
export function findCategoryPath(
  nodes: CategoryNode[],
  slug: string,
  trail: CategoryNode[] = [],
): CategoryNode[] | null {
  for (const node of nodes) {
    const next = [...trail, node];
    if (node.slug === slug) return next;
    const deeper = findCategoryPath(node.children, slug, next);
    if (deeper) return deeper;
  }
  return null;
}

export function CategoryBreadcrumb({ slug, tree, className }: Props) {
  const locale = getLocale();
  const path = findCategoryPath(tree, slug) ?? [];

  return (
    <nav aria-label="breadcrumb" className={cn('text-sm', className)}>
      <ol className="flex flex-wrap items-center gap-y-1 text-ink-500">
        <li>
          <Link
            href="/categories"
            className="hover:text-terracotta transition-colors"
          >
            {t('categories.all', 'الأقسام')}
          </Link>
        </li>
        {path.map((node, idx) => {
          const isLast = idx === path.length - 1;
          return (
            <li key={node.id} className="flex items-center">
              <ChevronLeft className="size-3 ms-2 me-2 text-ink-300 rtl:rotate-180" />
              {isLast ? (
                <span
                  aria-current="page"
                  className="text-ink-900 font-medium"
                >
                  {localized(node.name, locale)}
                </span>
              ) : (
                <Link
                  href={`/c/${node.slug}`}
                  className="hover:text-terracotta transition-colors"
                >
                  {localized(node.name, locale)}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
