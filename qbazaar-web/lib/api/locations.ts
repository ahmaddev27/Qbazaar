/**
 * Typed client for the public locations endpoints.
 *
 * The Qatar location tree is small (~hundreds of nodes) and changes rarely,
 * so the backend advertises a 24h cache window and we load the whole tree
 * up-front via `useQatarLocationsQuery`.
 */
import { api } from './client';
import type { Location, SuccessEnvelope } from './types';

const BASE = '/api/v1/locations';

export async function getQatarLocations(): Promise<Location[]> {
  const { data } = await api.get<SuccessEnvelope<Location[]>>(`${BASE}/qatar`);
  return data.data;
}
