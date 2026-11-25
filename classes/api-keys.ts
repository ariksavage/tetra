/**
 * Shapes used by Tetra's API key pages (pages/my/api-keys, pages/my/api-key).
 * See core/ApiKeys/ApiKeys.api.php.
 */

export type ApiKeyStatus = 'active' | 'expired' | 'revoked';

export interface ApiKey {
  id: number;
  name: string;
  /** "tetra_0123abcd…": enough to tell keys apart, never the key. */
  display: string;
  status: ApiKeyStatus;
  date_created: number;
  date_last_used: number;
  last_used_ip: string;
  /** 0: never expires. */
  date_expires: number;
  date_revoked: number;
  /** With ?all=1: whose it is. */
  owner?: string;
}

/** What a key can really do: actions by dimension. */
export type ApiKeyAccess = { [dimension: string]: string[] };

/** One key, with what its page needs to edit its role. */
export interface ApiKeyDetail extends ApiKey {
  /** Roles its role inherits. */
  inherits: number[];
  /** Permissions given to it directly. */
  permission_ids: number[];
  /** Permissions it gets from the roles it inherits. */
  inherited_permission_ids: number[];
  /** Roles it may inherit. */
  roles: { id: number; label: string; description: string }[];
  /** Permissions it may be given (never for users, roles, permissions, settings or keys). */
  permissions: { id: number; dimension: string; action: string; title: string; description: string }[];
  /** Its role's permissions that its owner also has: what it can do. */
  can: ApiKeyAccess;
  /** Its role's permissions its owner lacks, so it can't use them either. */
  beyond_owner: ApiKeyAccess;
}

export interface ApiKeyLogEntry {
  id: number;
  method: string;
  path: string;
  status: number;
  date: number;
}

/** How long a new key lasts, in days (0: never). */
export const API_KEY_EXPIRY: { days: number; label: string }[] = [
  { days: 0, label: 'Never expires' },
  { days: 30, label: '30 days' },
  { days: 90, label: '90 days' },
  { days: 365, label: '1 year' },
];

/** "Nov 25, 2026" from a Unix timestamp, or '' for none. */
export function formatKeyDate(timestamp: number, withTime = false): string {
  if (!timestamp) {
    return '';
  }
  const d = new Date(timestamp * 1000);
  return withTime
    ? d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
    : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
