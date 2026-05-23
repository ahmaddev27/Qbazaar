'use client';

/**
 * BlockUserButton — reusable "Block this user" control.
 *
 * Wraps a confirm dialog around `POST /users/{id}/block`. Designed to be
 * dropped on the public profile page + chat threads.
 *
 * On success: closes the dialog, fires a sonner toast, and calls the
 * optional `onBlocked` callback so the parent can hide the now-blocked
 * user's content optimistically.
 */
import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Loader2Icon, ShieldOffIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { blockUser } from '@/lib/api/users';
import { ApiClientError } from '@/lib/api/auth';
import { UserErrorCode } from '@/lib/api/types';
import { cn } from '@/lib/utils';

export interface BlockUserButtonProps {
  userId: string;
  userName: string;
  /** Visual variant of the trigger — `outline` (default) or `destructive`. */
  variant?: 'outline' | 'destructive';
  /** `sm` (default) or `default` height — same scale as shadcn Button sizes. */
  size?: 'sm' | 'default';
  /** Fired after the block succeeds; the parent can hide the user's content. */
  onBlocked?: () => void;
  className?: string;
}

export function BlockUserButton({
  userId,
  userName,
  variant = 'outline',
  size = 'sm',
  onBlocked,
  className,
}: BlockUserButtonProps) {
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);

  const mutation = useMutation({
    mutationFn: () => blockUser(userId),
    onSuccess: () => {
      toast.success(t('users.block.success'));
      queryClient.invalidateQueries({ queryKey: ['account', 'blocked-users'] });
      setOpen(false);
      onBlocked?.();
    },
    onError: (err) => {
      if (err instanceof ApiClientError) {
        if (err.code === UserErrorCode.AlreadyBlocked) {
          toast.info(t('users.block.already_blocked'));
          setOpen(false);
          return;
        }
        toast.error(
          translateMaybeKey(`account.errors.${err.code}`) ||
            translateMaybeKey(`auth.errors.${err.code}`) ||
            err.message,
        );
      } else {
        toast.error(t('auth.errors.unknown'));
      }
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger
        render={
          <Button
            type="button"
            variant={variant === 'destructive' ? 'destructive' : 'outline'}
            size={size}
            className={cn('rounded-full px-3 text-xs font-semibold', className)}
          />
        }
      >
        <ShieldOffIcon className="size-3.5" aria-hidden />
        <span>{t('users.block.button')}</span>
      </DialogTrigger>

      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {t('users.block.confirm_title', { name: userName })}
          </DialogTitle>
          <DialogDescription>{t('users.block.confirm_body')}</DialogDescription>
        </DialogHeader>

        <DialogFooter>
          <DialogClose
            render={
              <Button variant="outline" size="default" className="rounded-full">
                {t('users.block.cancel')}
              </Button>
            }
          />
          <Button
            type="button"
            variant="destructive"
            size="default"
            disabled={mutation.isPending}
            onClick={() => mutation.mutate()}
            className="rounded-full"
          >
            {mutation.isPending ? (
              <>
                <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
                {t('users.block.blocking')}
              </>
            ) : (
              t('users.block.confirm')
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
