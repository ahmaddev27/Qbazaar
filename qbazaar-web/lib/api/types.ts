/**
 * Shared TypeScript types mirroring qbazaar-contracts/openapi/v1.yaml.
 *
 * These shapes are 1:1 with the OpenAPI schemas and should change ONLY when
 * the contract changes. The Prism mock and the eventual Laravel backend both
 * promise these envelopes.
 */

// ── Auth domain ─────────────────────────────────────────────────────────────
export type AccountType = 'private' | 'business';

export type UserStatus =
  | 'active'
  | 'suspended'
  | 'deactivated'
  | 'pending_deletion';

export type Language = 'ar' | 'en';

export interface User {
  id: string;
  full_name: string;
  email: string;
  phone: string;
  account_type: AccountType;
  status: UserStatus;
  email_verified: boolean;
  phone_verified: boolean;
  language: Language;
  avatar_url: string | null;
  avatar_thumb_url?: string | null;
  avatar_medium_url?: string | null;
  created_at: string;
}

export interface Token {
  access_token: string;
  refresh_token: string;
  token_type: 'Bearer';
  expires_in: number;
}

// ── Request bodies ──────────────────────────────────────────────────────────
export interface RegisterRequest {
  full_name: string;
  email: string;
  phone: string;
  password: string;
  account_type: AccountType;
  language?: Language;
  accepted_terms: true;
}

export interface LoginRequest {
  identifier: string;
  password: string;
}

export interface RefreshRequest {
  refresh_token: string;
}

// ── Envelopes ──────────────────────────────────────────────────────────────
export interface SuccessEnvelope<T> {
  success: true;
  data: T;
}

export interface ApiError {
  code: string;
  message_key: string;
  message: string;
  details?: Record<string, string[]> | null;
  request_id?: string;
}

export interface ErrorEnvelope {
  success: false;
  error: ApiError;
}

export type AuthResponseData = {
  user: User;
  tokens: Token;
};

export type AuthResponseEnvelope = SuccessEnvelope<AuthResponseData>;

// ── OTP / password-reset / email-verification (Wave 2) ─────────────────────
export interface OtpSendRequest {
  phone: string;
}

export interface OtpVerifyRequest {
  phone: string;
  code: string;
}

export interface OtpSendResponseData {
  sent_to: string;
  expires_in: number;
  can_resend_in: number;
}

export type OtpSendResponseEnvelope = SuccessEnvelope<OtpSendResponseData>;

export interface OtpVerifyResponseData {
  phone_verified: true;
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ResetPasswordRequest {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface VerifyEmailQuery {
  signature?: string;
  expires?: string;
}

// Stable list of error codes the UI switches on (see error-codes.md).
export const AuthErrorCode = {
  InvalidCredentials: 'AUTH_001',
  AccountSuspended: 'AUTH_002',
  PhoneNotVerified: 'AUTH_003',
  OtpExpired: 'AUTH_004',
  OtpInvalid: 'AUTH_005',
  AuthRateLimited: 'AUTH_006',
  EmailExists: 'AUTH_007',
  PhoneExists: 'AUTH_008',
  TokenExpired: 'AUTH_009',
  TokenInvalid: 'AUTH_010',
  ValidationFailed: 'VALIDATION_FAILED',
  RateLimited: 'RATE_LIMIT_EXCEEDED',
} as const;

export type AuthErrorCodeValue =
  (typeof AuthErrorCode)[keyof typeof AuthErrorCode];

// ── Account / Users domain (Sprint 2) ──────────────────────────────────────
// Shapes are aligned with the upcoming OpenAPI schemas — the backend agent
// will land the contract update in this same wave.

/**
 * Counters surfaced on the authenticated account dashboard.
 * Backend: `GET /account/summary`.
 */
export interface AccountSummary {
  ads_count: number;
  drafts_count: number;
  conversations_count: number;
  unread_notifications_count: number;
}

/**
 * The full editable profile for the currently signed-in user.
 * Backend: `GET /account/profile`.
 */
export interface AccountProfile extends User {
  bio: string | null;
}

export interface UpdateProfileRequest {
  full_name: string;
  language: Language;
  bio?: string | null;
}

export interface ChangePasswordRequest {
  current_password: string;
  new_password: string;
  password_confirmation: string;
}

/**
 * Privacy toggles persisted in a JSON column on `users`.
 * Backend: `GET, PUT /account/privacy-settings`.
 */
export interface PrivacySettings {
  show_phone: boolean;
  show_email: boolean;
  allow_chat: boolean;
  indexed_by_search: boolean;
}

export interface VerificationStatus {
  email_verified: boolean;
  phone_verified: boolean;
  business_verified: boolean;
  kyc_verified: boolean;
}

/**
 * One row in the active-sessions list.
 * Backend: `GET /account/sessions`.
 */
export interface UserSession {
  id: string;
  device_label: string | null;
  ip_address: string | null;
  user_agent: string | null;
  last_used_at: string;
  created_at: string;
  is_current: boolean;
}

export interface BlockedUser {
  id: string;
  full_name: string;
  avatar_url: string | null;
  blocked_at: string;
}

export interface PublicUserProfile {
  id: string;
  full_name: string;
  avatar_url: string | null;
  account_type: AccountType;
  email_verified: boolean;
  phone_verified: boolean;
  ads_count: number;
  joined_at: string;
  bio: string | null;
}

/**
 * Minimal ad shape used by the public profile's "Ads" tab.
 * The real Ad schema lands in Sprint 4 — this is intentionally narrow.
 */
export interface PublicUserAd {
  id: string;
  title: string;
  price: number;
  currency: string;
  thumbnail_url: string | null;
  created_at: string;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface PaginatedEnvelope<T> {
  success: true;
  data: T[];
  meta: PaginationMeta;
}

export const UserErrorCode = {
  UserNotFound: 'USER_001',
  PasswordIncorrect: 'USER_002',
  AlreadyBlocked: 'USER_003',
  CannotBlockSelf: 'USER_004',
  PrivacyDenied: 'USER_005',
  // Wave 2 — account-lifecycle codes
  DeactivationPasswordRequired: 'USER_006',
  DeletionPasswordRequired: 'USER_007',
  DataExportPending: 'USER_008',
  AvatarInvalidImage: 'USER_009',
  AvatarTooLarge: 'USER_010',
} as const;

export type UserErrorCodeValue =
  (typeof UserErrorCode)[keyof typeof UserErrorCode];

// ── Account lifecycle / Avatar (Sprint 2 Wave 2) ───────────────────────────

/**
 * Response from `POST /account/data-export-request`.
 * The actual export file is delivered out-of-band via email — the API only
 * confirms the job has been queued.
 */
export interface DataExportResponse {
  status: 'queued' | 'processing' | 'ready';
  requested_at: string;
  estimated_ready_in_minutes?: number | null;
}

export interface DeactivateAccountRequest {
  password: string;
  reason?: string | null;
}

export interface DeleteAccountRequest {
  password: string;
  reason?: string | null;
}

/**
 * Response from `POST /uploads/avatar`. The backend returns three URLs:
 *
 * - `avatar_url`        full-resolution image (cropped 1:1 by the client)
 * - `avatar_thumb_url`  ~64px thumbnail used in lists + sidebar
 * - `avatar_medium_url` ~256px medium used on profile cards
 */
export interface AvatarUploadResponse {
  avatar_url: string;
  avatar_thumb_url: string;
  avatar_medium_url: string;
}
