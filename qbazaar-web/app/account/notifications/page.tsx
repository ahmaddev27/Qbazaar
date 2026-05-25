import { Suspense } from 'react';
import type { Metadata } from 'next';
import { NotificationsClient } from './NotificationsClient';

export const metadata: Metadata = {
  title: 'Notifications',
};

/**
 * Server shell — wraps the client tabs page in Suspense so nuqs can read the
 * `?tab=` parameter without de-opting the route to dynamic rendering.
 */
export default function NotificationsPage() {
  return (
    <Suspense fallback={null}>
      <NotificationsClient />
    </Suspense>
  );
}
