import type { Metadata } from 'next';
import { CategoryDetailClient } from './CategoryDetailClient';

interface PageProps {
  params: Promise<{ slug: string }>;
}

/**
 * Category detail — `/c/{slug}`.
 *
 * Sprint 3 placeholder: the listing surface (ads grid + pagination) lands
 * in Sprint 5. For now we render the breadcrumb, category header, an
 * empty-state for ads, the subcategories grid (if any), and a filters
 * sidebar so the API contract is already exercised end-to-end.
 *
 * The actual data lookup happens on the client; the slug is forwarded
 * untouched so we can also return a friendly 404 from inside the island
 * when the tree resolves and the slug isn't present.
 */
export async function generateMetadata({
  params,
}: PageProps): Promise<Metadata> {
  const { slug } = await params;
  // Title is enhanced with the resolved name client-side; this is just SEO
  // fallback for crawlers hitting the slug before hydration.
  return {
    title: slug,
  };
}

export default async function CategoryDetailPage({ params }: PageProps) {
  const { slug } = await params;
  return <CategoryDetailClient slug={slug} />;
}
