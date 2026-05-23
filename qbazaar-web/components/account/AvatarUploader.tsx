'use client';

/**
 * AvatarUploader (FE-2.13)
 *
 * A reusable card that owns the entire avatar-upload journey:
 *
 *   pick a file  →  client-side validate  →  crop 1:1 modal  →
 *   POST /uploads/avatar  →  patch the auth store so the new photo
 *   shows up immediately on the sidebar/header.
 *
 * The crop step is mandatory (and constrained to a 1:1 aspect) so every
 * avatar lands on the backend in the same shape — the server only has to
 * resize, not re-crop.
 *
 * Validation rules MUST mirror the backend (BE-2.12):
 *   - MIME: jpeg / png / webp only
 *   - Size: ≤ 5 MB
 *
 * After a successful upload we toast + call `setAvatarUrls` on the auth
 * store. We do NOT touch React Query directly here; the consumer can pass
 * `onUploaded` if it wants to invalidate a specific query.
 */
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import {
  Loader2Icon,
  UploadCloudIcon,
  CameraIcon,
  ZoomInIcon,
} from 'lucide-react';
import Cropper, { type Area } from 'react-easy-crop';

import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import { uploadAvatar } from '@/lib/api/uploads';
import { ApiClientError } from '@/lib/api/auth';
import { useAuthStore } from '@/store/auth';
import type { AvatarUploadResponse } from '@/lib/api/types';

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'] as const;
const MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const OUTPUT_SIZE = 512; // post-crop square edge, matches backend medium size
const OUTPUT_MIME = 'image/jpeg';
const OUTPUT_QUALITY = 0.9;

type AcceptedMime = (typeof ACCEPTED_TYPES)[number];

function isAcceptedMime(value: string): value is AcceptedMime {
  return (ACCEPTED_TYPES as readonly string[]).includes(value);
}

export interface AvatarUploaderProps {
  /**
   * The user's full name — used for the fallback initials when no avatar
   * is set yet. The component reads the URL straight from the auth store.
   */
  fullName: string;
  /** Optional hook fired after a successful upload (e.g. to invalidate queries). */
  onUploaded?: (urls: AvatarUploadResponse) => void;
  className?: string;
}

interface ValidationError {
  /** Translated error message ready to render. */
  message: string;
}

function validateFile(file: File): ValidationError | null {
  if (!isAcceptedMime(file.type)) {
    return { message: t('account.avatar.errors.type') };
  }
  if (file.size > MAX_BYTES) {
    return { message: t('account.avatar.errors.size') };
  }
  return null;
}

function readFileAsDataUrl(file: File | Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () =>
      typeof reader.result === 'string'
        ? resolve(reader.result)
        : reject(new Error('not-a-string'));
    reader.onerror = () => reject(reader.error ?? new Error('read-error'));
    reader.readAsDataURL(file);
  });
}

function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => reject(new Error('image-decode-failed'));
    img.src = src;
  });
}

/**
 * Crops the source image to the supplied 1:1 area and returns a JPEG `Blob`
 * sized to OUTPUT_SIZE px. Done off-DOM via a canvas so we don't ship a heavy
 * image lib for one crop.
 */
async function cropToBlob(
  imageSrc: string,
  area: Area,
): Promise<Blob> {
  const image = await loadImage(imageSrc);
  const canvas = document.createElement('canvas');
  canvas.width = OUTPUT_SIZE;
  canvas.height = OUTPUT_SIZE;
  const ctx = canvas.getContext('2d');
  if (!ctx) throw new Error('canvas-unsupported');

  ctx.drawImage(
    image,
    area.x,
    area.y,
    area.width,
    area.height,
    0,
    0,
    OUTPUT_SIZE,
    OUTPUT_SIZE,
  );

  return new Promise<Blob>((resolve, reject) => {
    canvas.toBlob(
      (blob) =>
        blob ? resolve(blob) : reject(new Error('canvas-to-blob-failed')),
      OUTPUT_MIME,
      OUTPUT_QUALITY,
    );
  });
}

function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0]!.slice(0, 2).toUpperCase();
  return (parts[0]![0]! + parts[parts.length - 1]![0]!).toUpperCase();
}

export function AvatarUploader({
  fullName,
  onUploaded,
  className,
}: AvatarUploaderProps) {
  const inputRef = useRef<HTMLInputElement | null>(null);

  const user = useAuthStore((s) => s.user);
  const setAvatarUrls = useAuthStore((s) => s.setAvatarUrls);

  const currentAvatar =
    user?.avatar_medium_url ?? user?.avatar_url ?? null;

  const [dragOver, setDragOver] = useState(false);
  const [inlineError, setInlineError] = useState<string | null>(null);

  // Crop modal state
  const [pickedImage, setPickedImage] = useState<string | null>(null);
  const [crop, setCrop] = useState<{ x: number; y: number }>({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedArea, setCroppedArea] = useState<Area | null>(null);

  // Release blob URLs the moment we don't need them.
  useEffect(() => {
    return () => {
      if (pickedImage?.startsWith('blob:')) {
        URL.revokeObjectURL(pickedImage);
      }
    };
  }, [pickedImage]);

  const mutation = useMutation({
    mutationFn: async (blob: Blob) => uploadAvatar(blob),
    onSuccess: (data) => {
      setAvatarUrls({
        avatar_url: data.avatar_url,
        avatar_thumb_url: data.avatar_thumb_url,
        avatar_medium_url: data.avatar_medium_url,
      });
      toast.success(t('account.avatar.uploaded'));
      onUploaded?.(data);
      closeCropModal();
    },
    onError: (err) => {
      if (err instanceof ApiClientError) {
        // 422 with per-field details → inline below the dropzone
        if (err.code === 'VALIDATION_FAILED' && err.details) {
          const firstField = Object.values(err.details)[0];
          const first = Array.isArray(firstField) ? firstField[0] : undefined;
          setInlineError(first ?? t('account.avatar.upload_failed'));
          return;
        }
        const translated =
          translateMaybeKey(`account.errors.${err.code}`) ||
          translateMaybeKey(`auth.errors.${err.code}`);
        setInlineError(translated || err.message);
        toast.error(translated || t('account.avatar.upload_failed'));
        return;
      }
      setInlineError(t('account.avatar.upload_failed'));
      toast.error(t('account.avatar.upload_failed'));
    },
  });

  const openCropModal = useCallback(async (file: File) => {
    setInlineError(null);
    const failed = validateFile(file);
    if (failed) {
      setInlineError(failed.message);
      return;
    }
    try {
      const dataUrl = await readFileAsDataUrl(file);
      setCrop({ x: 0, y: 0 });
      setZoom(1);
      setCroppedArea(null);
      setPickedImage(dataUrl);
    } catch {
      setInlineError(t('account.avatar.errors.read'));
    }
  }, []);

  const closeCropModal = useCallback(() => {
    setPickedImage(null);
    setCroppedArea(null);
    setCrop({ x: 0, y: 0 });
    setZoom(1);
    if (inputRef.current) inputRef.current.value = '';
  }, []);

  const onCropComplete = useCallback(
    (_area: Area, areaPixels: Area) => setCroppedArea(areaPixels),
    [],
  );

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) void openCropModal(file);
  };

  const handleDrop = (event: React.DragEvent<HTMLLabelElement>) => {
    event.preventDefault();
    setDragOver(false);
    const file = event.dataTransfer.files?.[0];
    if (file) void openCropModal(file);
  };

  const handleConfirmCrop = async () => {
    if (!pickedImage || !croppedArea) return;
    try {
      const blob = await cropToBlob(pickedImage, croppedArea);
      await mutation.mutateAsync(blob);
    } catch {
      setInlineError(t('account.avatar.upload_failed'));
      toast.error(t('account.avatar.upload_failed'));
    }
  };

  const acceptAttr = useMemo(() => ACCEPTED_TYPES.join(','), []);
  const uploading = mutation.isPending;

  return (
    <section
      aria-labelledby="avatar-uploader-title"
      className={cn(
        'bg-card ring-foreground/10 rounded-2xl p-5 ring-1 sm:p-7',
        className,
      )}
    >
      <header className="mb-4 space-y-1">
        <h2
          id="avatar-uploader-title"
          className="font-display text-ink-900 text-xl tracking-tight sm:text-2xl"
        >
          {t('account.avatar.title')}
        </h2>
        <p className="text-muted-foreground text-sm">
          {t('account.avatar.subtitle')}
        </p>
      </header>

      <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
        <Avatar
          size="lg"
          className="size-20 ring-1 ring-foreground/10 sm:size-24"
        >
          {currentAvatar ? (
            <AvatarImage
              src={currentAvatar}
              alt={fullName}
              className="object-cover"
            />
          ) : null}
          <AvatarFallback className="font-display text-coral bg-cream-200 text-2xl">
            {initials(fullName)}
          </AvatarFallback>
        </Avatar>

        <label
          onDragOver={(event) => {
            event.preventDefault();
            setDragOver(true);
          }}
          onDragLeave={() => setDragOver(false)}
          onDrop={handleDrop}
          className={cn(
            'group/avatar-drop relative flex flex-1 cursor-pointer flex-col items-center justify-center gap-1 rounded-2xl border-2 border-dashed px-4 py-6 text-center transition-colors',
            dragOver
              ? 'border-coral bg-coral/5'
              : 'border-border bg-muted/30 hover:border-coral/50 hover:bg-coral/5',
            uploading && 'pointer-events-none opacity-60',
          )}
        >
          <input
            ref={inputRef}
            type="file"
            accept={acceptAttr}
            className="sr-only"
            onChange={handleFileChange}
            disabled={uploading}
            aria-label={t('account.avatar.change')}
          />
          <UploadCloudIcon
            className={cn(
              'text-muted-foreground size-6 transition-colors',
              dragOver && 'text-coral',
            )}
            aria-hidden
          />
          <p className="text-ink-700 text-sm font-medium">
            {t('account.avatar.drop_label')}{' '}
            <span className="text-coral underline-offset-2 group-hover/avatar-drop:underline">
              {t('account.avatar.browse')}
            </span>
          </p>
          <p className="text-muted-foreground text-xs">
            {t('account.avatar.supported')}
          </p>

          {currentAvatar ? (
            <span className="text-muted-foreground mt-2 inline-flex items-center gap-1.5 text-xs">
              <CameraIcon className="size-3.5" aria-hidden />
              {t('account.avatar.change')}
            </span>
          ) : null}
        </label>
      </div>

      {inlineError ? (
        <p
          role="alert"
          className="border-destructive/30 bg-destructive/5 text-destructive mt-4 rounded-xl border px-3 py-2 text-sm"
        >
          {inlineError}
        </p>
      ) : null}

      <Dialog
        open={pickedImage !== null}
        onOpenChange={(open) => {
          if (!open && !uploading) closeCropModal();
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{t('account.avatar.crop_title')}</DialogTitle>
            <DialogDescription>
              {t('account.avatar.crop_subtitle')}
            </DialogDescription>
          </DialogHeader>

          <div className="bg-ink-900 relative aspect-square w-full overflow-hidden rounded-xl">
            {pickedImage ? (
              <Cropper
                image={pickedImage}
                crop={crop}
                zoom={zoom}
                aspect={1}
                cropShape="round"
                showGrid={false}
                onCropChange={setCrop}
                onZoomChange={setZoom}
                onCropComplete={onCropComplete}
              />
            ) : null}
          </div>

          <div className="space-y-1.5">
            <label
              htmlFor="avatar-zoom"
              className="text-muted-foreground flex items-center gap-2 text-xs font-medium"
            >
              <ZoomInIcon className="size-3.5" aria-hidden />
              {t('account.avatar.crop_zoom_label')}
            </label>
            <input
              id="avatar-zoom"
              type="range"
              min={1}
              max={3}
              step={0.01}
              value={zoom}
              onChange={(event) => setZoom(Number(event.target.value))}
              className="accent-coral w-full"
              disabled={uploading}
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              size="default"
              className="rounded-full"
              onClick={closeCropModal}
              disabled={uploading}
            >
              {t('account.avatar.crop_cancel')}
            </Button>
            <Button
              type="button"
              size="default"
              className="rounded-full"
              onClick={handleConfirmCrop}
              disabled={uploading || !croppedArea}
            >
              {uploading ? (
                <>
                  <Loader2Icon className="size-4 animate-spin" aria-hidden />
                  {t('account.avatar.uploading')}
                </>
              ) : (
                t('account.avatar.crop_confirm')
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </section>
  );
}
