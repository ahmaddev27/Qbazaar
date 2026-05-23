/**
 * Typed users API client — public profile + block / unblock interactions.
 *
 * Block + unblock require the Bearer token; public-profile and user-ads are
 * anonymous-friendly per the contract. All errors funnel through
 * `ApiClientError` so the UI can switch on the stable `USER_*` codes.
 */
import { api } from './client';
import { ApiClientError } from './auth';
import { isAxiosError } from 'axios';
import type {
  ErrorEnvelope,
  PaginatedEnvelope,
  PublicUserAd,
  PublicUserProfile,
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

export async function getPublicProfile(
  userId: string,
): Promise<PublicUserProfile> {
  try {
    const { data } = await api.get<SuccessEnvelope<PublicUserProfile>>(
      `/api/v1/users/${encodeURIComponent(userId)}/public-profile`,
    );
    return data.data;
  } catch (err) {
    throw toApiClientError(err);
  }
}

export interface GetUserAdsParams {
  page?: number;
  per_page?: number;
}

export async function getUserAds(
  userId: string,
  params: GetUserAdsParams = {},
): Promise<PaginatedEnvelope<PublicUserAd>> {
  try {
    const { data } = await api.get<PaginatedEnvelope<PublicUserAd>>(
      `/api/v1/users/${encodeURIComponent(userId)}/ads`,
      { params },
    );
    return data;
  } catch (err) {
    throw toApiClientError(err);
  }
}

export async function blockUser(userId: string): Promise<void> {
  try {
    await api.post(`/api/v1/users/${encodeURIComponent(userId)}/block`);
  } catch (err) {
    throw toApiClientError(err);
  }
}

export async function unblockUser(userId: string): Promise<void> {
  try {
    await api.delete(`/api/v1/users/${encodeURIComponent(userId)}/block`);
  } catch (err) {
    throw toApiClientError(err);
  }
}
