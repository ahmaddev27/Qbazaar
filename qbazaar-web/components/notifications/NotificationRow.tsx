'use client';

/**
 * One row on the notifications index page.
 *
 * Renders the full-width version of a notification — icon, title, body,
 * timestamp, unread dot, and a kebab menu for the "delete" action with a
 * confirm dialog. The whole row is a button that marks the row read +
 * (if present) navigates to `cta_url`. The delete menu stops propagation
 * so clicking it doesn't also mark+navigate.
 */
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { toast } from 'sonner';
import {
  Loader2Icon,
  MoreHorizontalIcon,
  Trash2Icon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DynamicIcon } from '@/components/ui/dynamic-icon';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  useDeleteNotificationMutation,
  useMarkNotificationReadMutation,
} from '@/lib/queries/notifications';
import { formatRelativeTime } from '@/components/messaging/relative-time';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import { ApiClientError } from '@/lib/api/auth';
import type { Notification } from '@/lib/api/types';

interface Props {
  notification: Notification;
}

export function NotificationRow({ notification: n }: Props) {
  const router = useRouter();
  const [confirmOpen, setConfirmOpen] = useState(false);
  const markRead = useMarkNotificationReadMutation();
  const deleteMutation = useDeleteNotificationMutation();

  const handleOpen = () => {
    if (!n.read_at) markRead.mutate(n.id);
    if (n.cta_url) router.push(n.cta_url);
  };

  const handleDelete = () => {
    deleteMutation.mutate(n.id, {
      onSuccess: () => {
        toast.success(t('common.delete', 'حذف'));
        setConfirmOpen(false);
      },
      onError: (err) => {
        const message =
          err instanceof ApiClientError
            ? err.message
            : t('common.error', 'حدث خطأ، حاول مرة أخرى');
        toast.error(message);
      },
    });
  };

  return (
    <article
      className={cn(
        'border-ink-200 bg-card relative flex items-start gap-3 rounded-2xl border p-4 transition-colors',
        !n.read_at && 'border-coral/30 bg-coral/5',
      )}
    >
      <button
        type="button"
        onClick={handleOpen}
        className="absolute inset-0 rounded-2xl focus-visible:ring-2 focus-visible:ring-coral/50 focus-visible:outline-none"
        aria-label={n.title}
      />

      <span
        className={cn(
          'bg-cream-200 text-ink-700 relative grid size-10 shrink-0 place-items-center rounded-full',
          !n.read_at && 'bg-coral/15 text-coral',
        )}
        aria-hidden
      >
        <DynamicIcon name={n.icon} className="size-5" />
      </span>

      <div className="relative min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <h3 className="text-ink-900 truncate text-sm font-semibold">
            {n.title}
          </h3>
          {!n.read_at ? (
            <span
              aria-hidden
              className="bg-coral size-1.5 shrink-0 rounded-full"
            />
          ) : null}
        </div>
        <p className="text-ink-500 mt-1 text-sm">{n.body}</p>
        <p className="text-ink-400 mt-1.5 text-xs">
          {formatRelativeTime(n.created_at)}
        </p>
      </div>

      <div className="relative shrink-0">
        <DropdownMenu>
          <DropdownMenuTrigger
            render={
              <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                aria-label={t('common.edit', 'إجراءات')}
              />
            }
          >
            <MoreHorizontalIcon className="size-4" aria-hidden />
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem
              variant="destructive"
              onClick={() => setConfirmOpen(true)}
            >
              <Trash2Icon className="size-3.5" aria-hidden />
              {t('notifications.delete', 'حذف')}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {t('notifications.delete_confirm.title', 'حذف الإشعار؟')}
              </DialogTitle>
              <DialogDescription>
                {t('notifications.delete_confirm.body', 'لا يمكن التراجع.')}
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <DialogClose
                render={
                  <Button variant="outline" size="default" className="rounded-full">
                    {t('common.cancel', 'إلغاء')}
                  </Button>
                }
              />
              <Button
                type="button"
                variant="destructive"
                size="default"
                disabled={deleteMutation.isPending}
                onClick={handleDelete}
                className="rounded-full"
              >
                {deleteMutation.isPending ? (
                  <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
                ) : null}
                {t('notifications.delete_confirm.confirm', 'حذف')}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </article>
  );
}
