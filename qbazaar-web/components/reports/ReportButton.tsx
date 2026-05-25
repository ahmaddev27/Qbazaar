'use client';

/**
 * Generic "Report" trigger used everywhere a user can flag content.
 *
 * Renders an outline button with a Flag icon and opens `ReportDialog`. The
 * dialog itself owns the form state, so this component is a thin wrapper —
 * callers just pass the polymorphic target.
 */
import { useState } from 'react';
import { FlagIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ReportDialog } from './ReportDialog';
import { useAuth } from '@/hooks/useAuth';
import { t } from '@/lib/i18n/messages';
import type { ReportTarget } from '@/lib/api/types';

interface Props {
  target_type: ReportTarget;
  target_id: string;
  /** Optional override class — handy when the call-site needs a pill style. */
  className?: string;
  /** Variant of the trigger button. Defaults to outline. */
  variant?: 'outline' | 'ghost';
}

export function ReportButton({
  target_type,
  target_id,
  className,
  variant = 'outline',
}: Props) {
  const { isAuthenticated, isHydrated } = useAuth();
  const [open, setOpen] = useState(false);

  // Only authenticated users can submit reports; hide the affordance so
  // signed-out visitors don't open a dialog that immediately errors out.
  if (!isHydrated || !isAuthenticated) return null;

  return (
    <>
      <Button
        type="button"
        variant={variant}
        size="default"
        className={className ?? 'rounded-full'}
        onClick={() => setOpen(true)}
      >
        <FlagIcon className="size-3.5" aria-hidden />
        {t('reports.title', 'إبلاغ')}
      </Button>

      <ReportDialog
        open={open}
        onOpenChange={setOpen}
        target_type={target_type}
        target_id={target_id}
      />
    </>
  );
}
