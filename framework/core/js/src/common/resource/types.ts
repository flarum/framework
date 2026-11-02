import type Mithril from 'mithril';
import type Model from '../Model';
import type Stream from '../utils/Stream';
import type { CommonFieldOptions } from '../components/FormGroup';
import type ResourceFormState from './ResourceFormState';

export type FieldKind = 'string' | 'integer' | 'number' | 'boolean' | 'date' | 'datetime' | 'array' | 'toOne' | 'toMany';

export type FormMode = 'create' | 'update';

/**
 * Hints the client can check before sending. The server stays authoritative: a check the client
 * cannot do exactly is skipped rather than made stricter.
 */
export interface FieldConstraints {
  /** Server-translated custom messages, keyed by the constraint they belong to (`pattern`, `maxLength`, `required`, …). */
  messages?: Record<string, string>;
  minLength?: number;
  maxLength?: number;
  /**
   * A JS `RegExp` source, compiled with `patternFlags` plus `u`. Skipped when it does not compile.
   * JS `\s` matches Unicode whitespace (e.g. U+00A0) where PCRE without `/u` matches ASCII only, so
   * the server may still reject a value that passes here.
   */
  pattern?: string;
  patternFlags?: string;
  /** Sent instead of `pattern` when there are several; the value must match all of them. */
  patterns?: { pattern: string; flags?: string }[];
  enum?: string[];
  /** Unknown formats are not checked. */
  format?: 'email' | 'uri' | 'uuid' | 'ulid' | 'ip' | 'ipv4' | 'ipv6' | 'hex-color' | (string & {});
  minimum?: number;
  maximum?: number;
  /** The value must be greater than this. */
  exclusiveMinimum?: number;
  /** The value must be less than this. */
  exclusiveMaximum?: number;
  multipleOf?: number;
  minItems?: number;
  maxItems?: number;
  uniqueItems?: boolean;
  /** Each array item must be one of `enum`. */
  items?: { enum?: string[] };
  /** The value must be one of `true`, `'yes'`, `'on'`, `1`, `'1'`, `'true'`. */
  accepted?: boolean;
  /** The value must be one of `false`, `'no'`, `'off'`, `0`, `'0'`, `'false'`. */
  declined?: boolean;
  /** If sent, the value must not be empty. */
  filled?: boolean;
  /** The key must be sent, even empty. Left to the server. */
  present?: boolean;
}

/**
 * One of Laravel's conditional required rules: `with` (any of `fields` present), `withAll` (all
 * present), `without` (any absent), `withoutAll` (all absent), `if` (`fields[0]` equals one of
 * `values`, where `null` matches an absent value), `unless` (equals none), `ifAccepted` / `ifDeclined` (`fields[0]` is accepted / declined).
 */
export interface RequiredCondition {
  rule: 'with' | 'withAll' | 'without' | 'withoutAll' | 'if' | 'unless' | 'ifAccepted' | 'ifDeclined';
  fields: string[];
  /** `if` and `unless` only. */
  values?: Array<string | null>;
}

/**
 * `FlarumField::ui()` hints. The server resolves `label` into the field's `label`, and translates a
 * `placeholder` that is a translation key.
 */
export interface FieldUiHints {
  /** `true` shows a column, a number shows one with that priority, `false` hides it. */
  column?: boolean | number;
  /** The attribute that labels a related record. */
  display?: string;
  /**
   * A string field's input: `'hidden'` gives none, `'color'` the colour preview input, `'icon'` the
   * icon input, any other value is the input type, rendered by the renderer registered for it on
   * the form's `InputFactory` (else by `FormGroup`).
   */
  widget?: 'hidden' | 'color' | 'icon' | (string & {});
  /** The input's placeholder. */
  placeholder?: string;
  /** Server-translated help text under the input. */
  help?: string;
  /** The column exists but starts hidden; the columns menu can show it. */
  hidden?: boolean;
  /** The input is shown only while any condition holds against the live form values. */
  visibleWhen?: RequiredCondition[];
  /** Show the required mark without checking it client-side, for `requiredConditional` fields. */
  markRequired?: boolean;
  /** Server-translated fieldset legend; fields of one group render together, in first-appearance order. */
  group?: string;
  /** Orders the field in its registry, higher first; descriptor order by default. */
  priority?: number;
  [key: string]: unknown;
}

/**
 * One field of a resource descriptor. Default-valued keys are omitted by the server; read the
 * mode-dependent flags through `isWritable()` / `isRequired()`.
 */
export interface FieldDescriptor {
  name: string;
  kind: FieldKind;
  /** Server-translated. */
  label: string;
  /** Default `true`. */
  visible?: boolean;
  writable?: { create?: boolean; update?: boolean };
  required?: { create?: boolean; update?: boolean };
  /** Per mode, the field is required when any condition holds; see `isRequired()`. */
  requiredWhen?: { create?: RequiredCondition[]; update?: RequiredCondition[] };
  /**
   * A required rule the client cannot evaluate (e.g. `Rule::requiredIf(closure)`): only the server
   * decides, so the client neither marks nor checks it beyond `required` and `requiredWhen`.
   */
  requiredConditional?: boolean;
  nullable?: boolean;
  sortable?: boolean;
  /** `writable` or `required` depends on the actual record; the flags are a best guess. */
  conditional?: boolean;
  constraints?: FieldConstraints;
  /** When present, replaces `constraints` in update mode; read both through `constraintsFor()`. */
  constraintsOnUpdate?: FieldConstraints;
  /** Server-translated label per enum value; values without one show as-is. */
  enumLabels?: Record<string, string>;
  /** Relationships only. */
  relationship?: { types: string[] };
  /** What the Create endpoint fills in when the field is absent; scalar kinds only. */
  default?: string | number | boolean;
  ui?: FieldUiHints;
}

export interface FilterDescriptor {
  key: string;
  /** Server-translated: the same-named field's label, else the filter's own translation. */
  label?: string;
  kind: 'string' | 'boolean' | 'enum' | 'relationship' | 'date' | 'number';
  /** A JSON Schema fragment for one value. */
  schema?: Record<string, unknown>;
  enum?: string[];
  enumLabels?: Record<string, string>;
  /** The related type of a `relationship` filter. */
  related?: string;
  /** The value is a comma-separated list. */
  multiple?: boolean;
  /** `date` / `number`: the value is `from..to`, either side may be empty; dates as `YYYY-MM-DD`. */
  range?: boolean;
  /** Always shown, never cleared. */
  required?: boolean;
  /** The value when the URL and the saved view have none. */
  default?: string;
  /** Whether `filter[-key]` negates it. */
  negatable?: boolean;
}

/**
 * The attributes of `GET /api/resource-schemas/{type}`, built per actor by the server.
 */
export interface ResourceDescriptor {
  schemaVersion: number;
  endpoint: string;
  /** Server-translated name of one record and of several. */
  label?: { one: string; other: string };
  /** The attributes that say whether the actor may edit / delete a record; absent means no such row action. */
  rowAbilities?: { edit?: string; delete?: string };
  capabilities: { list: boolean; create: boolean; delete?: boolean };
  pagination?: { defaultLimit: number; maxLimit: number };
  defaultSort?: string;
  /** Whether `filter[q]` searches this type. */
  search?: boolean;
  sorts?: string[];
  filters?: FilterDescriptor[];
  fields: FieldDescriptor[];
}

export type SaveData = Record<string, unknown> & { relationships?: Record<string, Model | Model[] | null> };

export interface FieldContext<M extends Model, V> {
  stream: Stream<V>;
  model: M | null;
  error?: string;
  form: ResourceFormState<M>;
}

/**
 * An input by type name plus its attrs, e.g. `{ type: 'relation', related: 'groups', multiple: true }`.
 * An `InputFactory` renders it through the renderer registered for the type, else through
 * `FormGroup` (`text`, `select`, `switch`, …).
 */
export interface InputSpec extends CommonFieldOptions {
  type: string;
  [attr: string]: any;
}

/**
 * What an input renderer gets besides its spec. Form inputs also get the form, the record and the
 * descriptor; filter inputs only the name, stream and label.
 */
export interface InputContext<V = unknown> {
  name: string;
  stream: Stream<V>;
  label?: Mithril.Children;
  help?: Mithril.Children;
  required?: boolean;
  error?: string;
  /** The id of the field's error message; point the control at it while `error` is set. */
  errorId?: string;
  descriptor?: FieldDescriptor;
  model?: Model | null;
  form?: ResourceFormState<any>;
}

export type InputRenderer = (spec: InputSpec, ctx: InputContext<any>) => Mithril.Children;

export interface FieldColumn<M extends Model> {
  render?(m: M): Mithril.Children;
  sort?: string;
  /** From a numeric `ui.column`; the field's own priority otherwise. */
  priority?: number;
  /** Starts hidden until the user shows it, from `ui.hidden`. */
  hidden?: boolean;
}

/**
 * One keyed entry of a resource's field registry: a table column and a form input.
 * Setting a facet to `false` hides it in that view.
 */
export interface Field<M extends Model, V = unknown> {
  label: Mithril.Children;
  column?: false | FieldColumn<M>;
  input?: false | InputSpec | ((ctx: FieldContext<M, V>) => Mithril.Children);
  value?(m: M | null): V;
  /** `initial` is the value the form started with, for serializers that diff below field level. */
  serialize?(v: V, initial?: V): SaveData;
  /**
   * `values` are the form's current input values (`ResourceFormState.liveValues()`), absent outside
   * forms and while the form builds its streams; return true then if the field depends only on them.
   */
  when?(m: M | null, values?: Record<string, unknown>): boolean;
  errorKeys?: string[];
  /**
   * The client-side errors of the field's form value, in place of the descriptor check. For a form
   * value that is not the attribute's value: a locale map, a composite input. Return no messages
   * when valid; several are joined into the field's `form.errors` entry, one line each.
   *
   * Called for every shown field, unless its descriptor is `conditional`, whether or not the value
   * changed: one unchanged value can turn invalid by another (a new locale row needs a name). Mind
   * `ctx.includeUnchanged` and `ctx.dirty` as the descriptor check does: while updating, only what
   * is sent should be checked.
   */
  validate?(value: V, ctx: FieldValidationContext<M, V>): string[];
  /**
   * Called with every JSON:API error of a failed save (422/403), after they were mapped onto
   * `form.errors`. For fields whose state spans more than one input.
   */
  onerrors?(errors: JsonApiError[], form: ResourceFormState<any>): void;
  /**
   * Called when `save()` stops at client-side validation, after the messages landed on
   * `form.errors`: `errors` holds them by field key, as `ValidationError.errors`. For fields whose
   * state spans more than one input, e.g. to bring an erroring part into view.
   */
  onclienterrors?(errors: Record<string, string[]>, form: ResourceFormState<any>): void;
  /** Present when the field was inferred. */
  descriptor?: FieldDescriptor;
}

/**
 * What `Field.validate()` gets besides the form value.
 */
export interface FieldValidationContext<M extends Model, V = unknown> {
  /** The field's key in the form. */
  key: string;
  form: ResourceFormState<M>;
  /** Absent for a field that was not inferred. */
  descriptor?: FieldDescriptor;
  mode: FormMode;
  /** The value the form started with. */
  initial: V;
  /** `form.values()`: what the server's conditional required rules see. */
  values: Record<string, unknown>;
  /** Whether unchanged values are checked too: on save while creating. */
  includeUnchanged: boolean;
  /** Whether the field's value changed, i.e. is sent. */
  dirty: boolean;
  /**
   * Core's check of one value, by default against the field's descriptor (none without one) and
   * with `values`; `sent` (for `filled`) defaults to true.
   */
  check(value: unknown, descriptor?: FieldDescriptor, options?: { values?: Record<string, unknown>; sent?: boolean }): string[];
}

export type InputResolver = (d: FieldDescriptor) => Partial<Field<any>> | null;

/**
 * Maps a described filter to its input. `d` is the descriptor of the same-named field, null when
 * there is none. Return `null` to fall through to the next resolver.
 */
export type FilterResolver = (filter: FilterDescriptor, d: FieldDescriptor | null) => Filter['input'] | null;

export interface FilterContext {
  stream: Stream<string>;
}

/**
 * A list filter, sent as `filter[{key}]`. An empty value is omitted.
 */
export interface Filter {
  label: Mithril.Children;
  input: InputSpec | ((ctx: FilterContext) => Mithril.Children);
  /**
   * The described filter's kind, when the filter was inferred.
   */
  kind?: FilterDescriptor['kind'];
  /**
   * "No" (`'0'`) is sent as the negated key, `filter[-key]=1`, instead of `filter[key]=0`: core's
   * filter manager negates a leading `-`, and boolean filters such as suspend's `suspended` ignore
   * the value. Inferred boolean filters, and filters named after a boolean field, are negatable.
   * `ResourceListState.isNegatable()` reads it.
   */
  negatable?: boolean;
  /** Always shown and never cleared; the list falls back to `default` while it has no value. */
  required?: boolean;
  /** The value when neither the URL nor the saved view has one. */
  default?: string;
  /**
   * Leaves records out of a relationship filter's menu, e.g. a group no user can be in.
   */
  exclude?: (model: Model) => boolean;
  /**
   * Whether the toolbar leaves this filter to the search box when a gambit of the same key covers
   * it. Default true; `false` keeps its dropdown.
   */
  hideWhenSearchable?: boolean;
}

export interface JsonApiError {
  status?: string;
  code?: string;
  title?: string;
  detail?: string;
  source?: { pointer?: string; parameter?: string };
}
