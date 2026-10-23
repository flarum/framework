import type { RunLike, RunState } from './runState';

export type { RunState };

/** What the server sends back, endpoint by endpoint. */

export type RunMode = 'update' | 'install' | 'remove';

export interface Run extends RunLike {
  id: string;
  state: RunState;
  phase: string;
  items: string[];
  index: number;
  log: string[];
  error: string | null;
  errorStep: string | null;
  startedAt?: number;
  movedAt?: number;
  packages: string[];
  mode: RunMode;
  /** The database changes undoing this run would reverse. */
  migrations: string[];
}

export interface UpdateHint {
  from: string;
  to: string;
  /** Where the release notes live, when there are any. */
  notes?: string;
}

export interface Updates {
  available: Record<string, UpdateHint>;
  checkedAt: number | null;
  stale: boolean;
  uncheckable: string[];
  tracking: string[];
}

export interface ExtensionIcon {
  name?: string;
  color?: string;
  backgroundColor?: string;
}

export interface Installed {
  id: string;
  name: string;
  package: string;
  version: string;
  icon: ExtensionIcon | null;
  enabled: boolean | null;
  /** A Composer library composer.json requires itself, e.g. fof/redis: no settings page. */
  library?: boolean;
  /** A hint from the cheap check: a newer version exists. Not a promise. */
  update: UpdateHint | null;
  /** What composer.json requires, e.g. "3.5.1", "^3.5", "dev-main". */
  constraint?: string | null;
  /** Installed from a local path, i.e. a symlink into somebody's checkout. */
  pathInstall: boolean;
}

export type TranslationParams = Record<string, string>;

/** A line of the host report that arrives as a translation key. */
export interface TranslatedLine {
  key: string;
  params?: TranslationParams;
}

export interface HostCheck {
  id: string;
  ok: boolean;
  warn: boolean;
  what?: string;
  why?: string;
  whatKey?: string;
  whatParams?: TranslationParams;
  whyKeys?: TranslatedLine[];
  override?: string | null;
}

export interface HostReport {
  checks: HostCheck[];
  summary?: string;
  summaryKey?: string | null;
}

export interface HistoryChange {
  package: string;
  op?: 'add' | 'remove' | 'update';
  from?: string;
  to?: string;
}

export interface HistoryEntry {
  id: string;
  at: number;
  state: RunState;
  mode: RunMode;
  changes?: HistoryChange[];
  requested?: string[];
  migrations?: number;
}

export interface StatePayload {
  host: HostReport;
  installed: Installed[];
  run: Run | null;
  /** The second half of an undo runs on this request; set if it could not. */
  undoError: string | null;
  history: HistoryEntry[];
  runIsStale: boolean;
  updates?: Updates;
}

export interface StepPayload {
  run: Run | null;
  idle?: boolean;
  busy?: boolean;
  stale?: boolean;
}

export interface StartPayload {
  run: Run;
  driver?: string | null;
}

export interface RollbackPayload {
  run: Run;
  /** What still has to happen, when the rollback could not finish everything. */
  next?: string | null;
}

export interface CheckPayload {
  updates?: Updates;
}

export type CoreRowState = 'ready' | 'needs-update' | 'blocked' | 'unknown';

export interface CoreRow {
  package: string;
  installed: string;
  state: CoreRowState;
  requires: string | null;
  to: string | null;
}

export interface CoreOverride {
  package: string;
  classes: string[];
}

export interface Preflight {
  target: string;
  verdicts: CoreRow[];
  pending: string[];
  blocked: number;
  updating: number;
  overrides?: CoreOverride[];
}

export interface CorePayload {
  current: string;
  newest: { from: string; to: string } | null;
  preflight: Preflight | null;
}

/** Whether a package's newest release says it works with this Flarum. */
export interface Verdict {
  compatible: boolean | null;
  version: string | null;
  requires: string | null;
  stability: string | null;
  /** True when this name is a dead end another package took over. */
  replaced?: boolean;
  /** That package, when it could be named. */
  replacedBy?: string | null;
}

export interface CompatPayload {
  verdicts: Record<string, Verdict>;
  /** The Flarum version the verdicts were worked out against. */
  core?: string;
}

export interface Found {
  name: string;
  description: string;
  downloads: number;
  favers: number;
  repository: string;
  /** The replacement's name when the author gave one, or true. */
  abandoned: boolean | string;
  installed: boolean;
}

export interface DiscoverPayload {
  results: Found[];
  installedNames?: string[];
  more?: boolean;
  error?: string | null;
}

export interface Release {
  name: string;
  tag: string;
  url: string;
  publishedAt: string | null;
  html: string;
}

export interface ReleaseNotesPayload {
  releases: Release[];
  url: string | null;
}

export interface Repository {
  type: string;
  url: string;
}

export interface StoredCredential {
  kind: string;
  host: string;
  detail: string;
}

export interface ConfigPayload {
  repositories: Repository[];
  types?: string[];
  stability: { minimumStability: string; preferStable: boolean; levels?: string[] };
  auth: { stored: StoredCredential[]; kinds?: string[] };
  note: string;
}

export interface TrashPayload {
  settings: { keepDays: number; keepRuns: number };
  trashBytes: number;
  trashCount: number;
  keptCount: number;
  removeCount: number;
  removeBytes: number;
  lastPrune: { at: number; removed: number; freed: number } | null;
  pruned: { removed: number; freed: number } | null;
}

/** The body of a refused request: Flarum's JSON:API errors, or the older single message. */
export interface ErrorPayload {
  errors?: { detail?: string; code?: string }[];
  error?: string;
  errorKey?: string;
  errorParams?: TranslationParams;
  /** A refused start names the run already going. */
  run?: Run;
  stale?: boolean;
}
