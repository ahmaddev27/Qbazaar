import axios, { type AxiosInstance } from 'axios';

/**
 * Shared axios instance used by every TanStack Query call against the API.
 *
 * In development NEXT_PUBLIC_API_URL points at the Prism mock server
 * (`http://localhost:4010`) until each endpoint exists in the real backend,
 * then it can be flipped to `http://localhost:8000`.
 *
 * Interceptors (auth bearer + 401 refresh dance) land in Sprint 1.
 */
export const api: AxiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:4010',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: false,
  timeout: 15_000,
});
