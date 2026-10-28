import type Mithril from 'mithril';
import type Model from '../Model';
import type Stream from '../utils/Stream';
import type { CommonFieldOptions } from '../components/FormGroup';
import type ResourceFormState from './ResourceFormState';

export type FieldKind = 'string' | 'integer' | 'number' | 'boolean' | 'date' | 'datetime' | 'array' | 'toOne' | 'toMany';

export type FormMode = 'create' | 'update';

/**
 * Hints the client can check before sending. The server stays authoritative.
 */
export interface FieldConstraints {
  minLength?: number;
  maxLength?: number;
  /** A JS `RegExp` source, compiled with `patternFlags`. */
  pattern?: string;
  patternFlags?: string;
  enum?: string[];
  format?: 'email' | 'url' | string;
  minimum?: number;
  maximum?: number;
  minItems?: number;
  maxItems?: number;
}

/**
 * `FlarumField::ui()` hints. The admin consumes `column`, `display`, `widget` and `placeholder`;
 * `group` and `priority` are accepted but currently ignored. The server resolves `label` into the
 * field's `label`, and translates a `placeholder` that is a translation key.
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
  nullable?: boolean;
  sortable?: boolean;
  /** `writable` or `required` depends on the actual record; the flags are a best guess. */
  conditional?: boolean;
  constraints?: FieldConstraints;
  /** Relationships only. */
  relationship?: { types: string[] };
  /** What the Create endpoint fills in when the field is absent; scalar kinds only. */
  default?: string | number | boolean;
  ui?: FieldUiHints;
}

export interface FilterDescriptor {
  key: string;
  kind: 'string' | 'boolean' | 'enum' | 'relationship';
  enum?: string[];
  /** The related type of a `relationship` filter. */
  related?: string;
}

/**
 * The attributes of `GET /api/resource-schemas/{type}`, built per actor by the server.
 */
export interface ResourceDescriptor {
  schemaVersion: number;
  endpoint: string;
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
  when?(m: M | null): boolean;
  errorKeys?: string[];
  /**
   * Called with every JSON:API error of a failed save (422/403), after they were mapped onto
   * `form.errors`. For fields whose state spans more than one input.
   */
  onerrors?(errors: JsonApiError[], form: ResourceFormState<any>): void;
  /** Present when the field was inferred. */
  descriptor?: FieldDescriptor;
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
   * the value. Inferred boolean filters are negatable. This is the source of truth for
   * `ResourceListState.isNegatable()`; left undefined, the descriptor decides.
   */
  negatable?: boolean;
  /**
   * Leaves records out of a relationship filter's menu, e.g. a group no user can be in.
   */
  exclude?: (model: Model) => boolean;
  /**
   * Leave this filter out of the toolbar (`true`) or keep it (`false`) when a gambit of the same
   * key covers it; `false` keeps a covered filter's dropdown even under the default
   * `searchCoveredFilters: 'all'`. Left undefined, the table's `searchCoveredFilters` decides.
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
