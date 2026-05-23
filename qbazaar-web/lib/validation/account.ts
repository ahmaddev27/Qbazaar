/**
 * Zod schemas for the authenticated account area.
 *
 * Mirrors the contract in `qbazaar-contracts/openapi/v1.yaml`
 * (UpdateProfileRequest, ChangePasswordRequest, PrivacySettings). The backend
 * remains the source of truth — client-side validation only short-circuits
 * obviously bad input.
 *
 * Error messages are i18n keys (e.g. `account.errors.full_name_min`) so the
 * shared `FieldError` component renders the localised string.
 */
import { z } from 'zod';

// Password rules MUST stay identical to the registration rules so the backend
// doesn't reject what we accepted client-side.
const passwordRules = z
  .string()
  .min(8, 'auth.errors.password_min')
  .regex(/[A-Z]/, 'auth.errors.password_uppercase')
  .regex(/[a-z]/, 'auth.errors.password_lowercase')
  .regex(/[0-9]/, 'auth.errors.password_number')
  .regex(/[^A-Za-z0-9]/, 'auth.errors.password_symbol');

// ── Profile ────────────────────────────────────────────────────────────────
export const profileSchema = z.object({
  full_name: z
    .string()
    .trim()
    .min(3, 'auth.errors.full_name_min')
    .max(80, 'auth.errors.full_name_max'),
  language: z.enum(['ar', 'en']),
  bio: z
    .string()
    .trim()
    .max(280, 'account.errors.bio_max')
    // Empty string normalises to null so the backend doesn't store ""
    .transform((value) => (value.length === 0 ? null : value))
    .nullable()
    .optional(),
});

export type ProfileInput = z.infer<typeof profileSchema>;

// ── Password ───────────────────────────────────────────────────────────────
export const changePasswordSchema = z
  .object({
    current_password: z
      .string()
      .min(1, 'auth.errors.password_required'),
    new_password: passwordRules,
    password_confirmation: z
      .string()
      .min(1, 'auth.errors.password_confirmation_required'),
  })
  .refine(
    (data) => data.new_password === data.password_confirmation,
    {
      path: ['password_confirmation'],
      message: 'auth.errors.password_mismatch',
    },
  )
  .refine(
    (data) => data.new_password !== data.current_password,
    {
      path: ['new_password'],
      message: 'account.errors.password_same_as_current',
    },
  );

export type ChangePasswordInput = z.infer<typeof changePasswordSchema>;

// ── Privacy settings ───────────────────────────────────────────────────────
export const privacySettingsSchema = z.object({
  show_phone: z.boolean(),
  show_email: z.boolean(),
  allow_chat: z.boolean(),
  indexed_by_search: z.boolean(),
});

export type PrivacySettingsInput = z.infer<typeof privacySettingsSchema>;
