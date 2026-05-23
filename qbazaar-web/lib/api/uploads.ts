/**
 * Uploads API client.
 *
 * Centralises every multipart/form-data request so the auth interceptors,
 * error shape, and progress-event plumbing stay consistent. Wave 2 only
 * needs avatar uploads — listing photos and document uploads land in later
 * sprints.
 *
 * Contract source of truth: `qbazaar-contracts/openapi/v1.yaml` (BE-2.12).
 */
import { isAxiosError } from 'axios';
import { api } from './client';
import { ApiClientError } from './auth';
import type {
  AvatarUploadResponse,
  ErrorEnvelope,
  SuccessEnvelope,
} from './types';

function toApiClientError(err: unknown): ApiClientError {
  if (isAxiosError<ErrorEnvelope>(err) && err.response?.data?.error) {
    const e = err.response.data.error;
    return new ApiClientError({
      status: err.response.status,
      code: e.code,
      messageKey: e.message_key,
      message: e.message,
      details: e.details,
      requestId: e.request_id,
    });
  }
  if (err instanceof Error) {
    return new ApiClientError({
      status: 0,
      code: 'NETWORK_ERROR',
      messageKey: 'errors.network',
      message: err.message,
    });
  }
  return new ApiClientError({
    status: 0,
    code: 'UNKNOWN_ERROR',
    messageKey: 'errors.unknown',
    message: 'Unknown error',
  });
}

export interface UploadAvatarOptions {
  /** Optional callback (0–100) fired as the browser streams the file. */
  onProgress?: (percent: number) => void;
  /** Lets a parent component abort the request — wired to AbortController. */
  signal?: AbortSignal;
}

/**
 * Uploads a cropped 1:1 avatar image (jpeg/png/webp, ≤ 5 MB).
 *
 * The caller is responsible for cropping + size validation; the backend
 * still re-validates and returns USER_009 / USER_010 on rejection.
 *
 * Returns the three image URLs the backend generates (full + thumb + medium).
 */
export async function uploadAvatar(
  file: File | Blob,
  options: UploadAvatarOptions = {},
): Promise<AvatarUploadResponse> {
  try {
    const form = new FormData();
    // The contract names the part `avatar`. A filename hint helps the
    // backend infer the extension when the source is a `Blob`.
    const filename =
      file instanceof File && file.name ? file.name : 'avatar.jpg';
    form.append('avatar', file, filename);

    const { data } = await api.post<SuccessEnvelope<AvatarUploadResponse>>(
      '/api/v1/uploads/avatar',
      form,
      {
        // Let the browser set the multipart boundary header automatically.
        headers: { 'Content-Type': 'multipart/form-data' },
        signal: options.signal,
        onUploadProgress: (event) => {
          if (!options.onProgress || !event.total) return;
          const percent = Math.round((event.loaded * 100) / event.total);
          options.onProgress(Math.min(100, Math.max(0, percent)));
        },
      },
    );
    return data.data;
  } catch (err) {
    throw toApiClientError(err);
  }
}
