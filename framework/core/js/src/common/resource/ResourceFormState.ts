import app from '../app';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import type RequestError from '../utils/RequestError';
import Stream from '../utils/Stream';
import generateElementId from '../utils/generateElementId';
import { conditionHolds, isRequired, isWritable } from './descriptor';
import { mapErrors } from './errors';
import { fieldSave, fieldValue, mergeSaveData, snapshot } from './field';
import type ResourceDefinition from './ResourceDefinition';
import type { Field, FieldError, FormMode, JsonApiError, ResolvedError, SaveData } from './types';
import { ValidationError, validateValue } from './validation';

/**
 * The editing state of one record. Only changed fields are sent: the server answers 403 for any
 * non-writable field in the payload. Client-side validation checks each value against its
 * descriptor; a field whose form value is not the attribute's value brings its own
 * `Field.validate()`.
 */
export default class ResourceFormState<M extends Model = Model> {
  /** The record being edited; null while creating. Replaced by the saved record. */
  model: M | null;
  fields: ItemList<Field<M>>;
  streams: Record<string, Stream<unknown>> = {};
  initial: Record<string, unknown> = {};
  errors: Record<string, string> = {};
  saving: boolean = false;
  /** The errors behind entries in `errors`, by key: client-side ones, or `ResolvedError`s from the server. */
  protected errorsByKey = new Map<string, FieldError[]>();
  protected sharedStreams: Record<string, Stream<any>> = {};
  /** A page-unique prefix for the ids of the form's elements, e.g. its error messages. */
  readonly domId: string = generateElementId();

  static async create<M extends Model>(definition: ResourceDefinition<M>, model: M | null = null): Promise<ResourceFormState<M>> {
    await definition.load();

    return new ResourceFormState<M>(definition, model);
  }

  constructor(
    public readonly definition: ResourceDefinition<M>,
    model: M | null = null
  ) {
    this.model = model;
    this.fields = definition.fields();
    this.reset();
  }

  isCreating(): boolean {
    return !this.model?.exists;
  }

  mode(): FormMode {
    return this.isCreating() ? 'create' : 'update';
  }

  /**
   * Fields with an input, passing `when(model)`, and (when inferred) writable in the current mode:
   * the fields that get a stream. `when()` gets no form values here.
   */
  inputFields(): ItemList<Field<M>> {
    const fields = new ItemList<Field<M>>();
    const mode = this.mode();

    Object.entries(this.fields.toObject()).forEach(([key, item]) => {
      const field = item.content as Field<M>;

      if (!field.input) return;
      if (field.when && !field.when(this.model)) return;
      if (field.descriptor && !isWritable(field.descriptor, mode)) return;

      fields.add(key, field, item.priority);
    });

    return fields;
  }

  /**
   * The input fields whose `when()` and `ui.visibleWhen` hold against `liveValues()`. A hidden field
   * is neither validated nor sent.
   */
  visibleFields(): ItemList<Field<M>> {
    const visible = new ItemList<Field<M>>();
    const inputs = this.inputFields();
    const shown = this.shownKeys();

    Object.entries(inputs.toObject()).forEach(([key, item]) => {
      if (shown.has(key)) visible.add(key, item.content as Field<M>, item.priority);
    });

    return visible;
  }

  isVisible(key: string): boolean {
    return this.shownKeys().has(key);
  }

  isShown(field: Field<M>, values: Record<string, unknown>): boolean {
    if (field.when && !field.when(this.model, values)) return false;

    const conditions = field.descriptor?.ui?.visibleWhen;

    return !conditions?.length || conditions.some((condition) => conditionHolds(condition, values));
  }

  /**
   * The keys of the shown fields. A hidden field counts as absent for the others, so hiding one can
   * hide (or show) a field that depends on it: iterated until nothing changes, at most once per
   * field.
   */
  protected shownKeys(): Set<string> {
    const keys = Object.keys(this.streams).filter((key) => this.fields.has(key));
    let shown = new Set(keys);

    for (let pass = 0; pass <= keys.length; pass++) {
      const values = this.valuesOf(shown);
      const next = new Set(keys.filter((key) => this.isShown(this.fields.get(key), values)));

      if (next.size === shown.size && [...next].every((key) => shown.has(key))) break;

      shown = next;
    }

    return shown;
  }

  /**
   * Every shown input's value by field name; unlike `values()`, unchanged ones included while
   * updating.
   */
  liveValues(): Record<string, unknown> {
    return this.valuesOf(this.shownKeys());
  }

  protected valuesOf(keys: Set<string>): Record<string, unknown> {
    const values: Record<string, unknown> = {};

    keys.forEach((key) => {
      values[this.fields.get(key).descriptor?.name ?? key] = this.streams[key]();
    });

    return values;
  }

  /**
   * A new record's empty field starts from the descriptor default unless the field has its own
   * `value()`; being the initial value, it is not sent (the Create endpoint fills in the same).
   */
  reset(): void {
    this.streams = {};
    this.initial = {};
    this.errors = {};
    this.errorsByKey.clear();

    const creating = this.isCreating();

    Object.entries(this.inputFields().toObject()).forEach(([key, item]) => {
      const field = item.content as Field<M>;
      let value = fieldValue(key, field, this.model);

      if (creating && value === null && !field.value && field.descriptor?.default !== undefined) {
        value = field.descriptor.default;
      }

      this.initial[key] = value;
      this.streams[key] = Stream(value);
    });
  }

  /**
   * Form-wide view state several inputs share, created on first use. Never sent, and kept across
   * `reset()`.
   */
  shared<V>(key: string, initial: V): Stream<V> {
    return (this.sharedStreams[key] ??= Stream(initial));
  }

  /**
   * The values the server's conditional required rules see, i.e. what `save()` sends: every shown
   * input while creating, the changed shown ones while updating. The rest count as absent.
   */
  values(): Record<string, unknown> {
    const values: Record<string, unknown> = {};
    const creating = this.isCreating();
    const visible = this.visibleFields();

    Object.keys(this.streams).forEach((key) => {
      if (!visible.has(key) || (!creating && !this.isDirty(key))) return;

      values[this.fields.get(key).descriptor?.name ?? key] = this.streams[key]();
    });

    return values;
  }

  /** Its descriptor's `required`, or a `requiredWhen` condition holding for `values()`. */
  isRequired(key: string): boolean {
    const descriptor = this.fields.has(key) ? this.fields.get(key).descriptor : undefined;

    return descriptor ? isRequired(descriptor, this.mode(), this.values()) : false;
  }

  stream<V = unknown>(key: string): Stream<V> {
    return this.streams[key] as Stream<V>;
  }

  /** Its field's `isChanged()`, else whether the value's snapshot differs from the initial one. */
  isDirty(key: string): boolean {
    if (!(key in this.streams)) return false;

    const isChanged = this.fields.get(key).isChanged;

    if (isChanged) return isChanged(this.streams[key](), this.initial[key]);

    return snapshot(this.streams[key]()) !== snapshot(this.initial[key]);
  }

  /**
   * The changed fields that are shown; a hidden field's change is kept but not sent.
   */
  dirtyKeys(): string[] {
    const visible = this.visibleFields();

    return Object.keys(this.streams).filter((key) => visible.has(key) && this.isDirty(key));
  }

  buildSaveData(): SaveData {
    return mergeSaveData(...this.dirtyKeys().map((key) => fieldSave(key, this.fields.get(key), this.streams[key](), this.initial[key])));
  }

  /**
   * Every shown field while creating, the changed ones while updating, by key: their messages.
   * Fields without a descriptor and `conditional` ones are left to the server.
   */
  validate(): Record<string, string[]> {
    return messagesOf(this.validateErrors());
  }

  /** `validate()` with the errors as `Field.validate()` gave them, plain messages as `{ message }`. */
  validateErrors(): Record<string, FieldError[]> {
    const errors: Record<string, FieldError[]> = {};

    Object.entries(this.visibleFields().toObject()).forEach(([key, item]) => {
      const fieldErrors = this.check(key, item.content as Field<M>, this.isCreating());

      if (fieldErrors.length) errors[key] = fieldErrors;
    });

    return errors;
  }

  /**
   * The errors shown for a field (`'*'`: the unmatched server errors), as `save()` or
   * `validateField()` left them: client-side ones as `{ message, path? }`, the server's as
   * `ResolvedError`s (see `isServerError()`).
   */
  fieldErrors(key: string): FieldError[] {
    return this.errorsByKey.get(key) ?? [];
  }

  /** Whether the field's errors came from the server; they stay until a save or a client error replaces them. */
  protected hasClientErrors(key: string): boolean {
    return !!this.errorsByKey.get(key)?.some((error) => !isServerError(error));
  }

  /**
   * Show or clear one field's client-side error, e.g. on blur, and return its messages. Other
   * client-side errors are rechecked, as a value can make a sibling (not) required.
   */
  validateField(key: string): string[] {
    if (!this.fields.has(key)) return [];

    const fieldErrors = this.check(key, this.fields.get(key), false);

    this.errorsByKey.forEach((_, other) => {
      if (other === key || !this.fields.has(other) || !this.hasClientErrors(other)) return;
      if (this.check(other, this.fields.get(other), this.isCreating()).length) return;

      delete this.errors[other];
      this.errorsByKey.delete(other);
    });

    if (fieldErrors.length) {
      this.setErrors(key, fieldErrors);
    } else if (this.hasClientErrors(key)) {
      delete this.errors[key];
      this.errorsByKey.delete(key);
    }

    return fieldErrors.map((error) => error.message);
  }

  /**
   * One field's client-side errors: none for a hidden or `conditional` field, else `field.validate()`
   * when it has one (called whether or not the value changed, see there), else the descriptor check
   * of a changed value, or of any while `includeUnchanged`. Fields without either are left to the
   * server. Not an extension point: give the field a `validate()` instead.
   */
  protected check(key: string, field: Field<M>, includeUnchanged: boolean): FieldError[] {
    const descriptor = field.descriptor;

    if (descriptor?.conditional || !this.isVisible(key)) return [];

    const mode = this.mode();
    const values = this.values();
    const dirty = this.isDirty(key);

    if (field.validate) {
      const errors = field.validate(this.streams[key](), {
        key,
        form: this,
        descriptor,
        mode,
        initial: this.initial[key],
        values,
        includeUnchanged,
        dirty,
        check: (value, d = descriptor, options = {}) => (d ? validateValue(d, value, mode, { values, ...options }) : []),
      });

      return errors.map((error) => (typeof error === 'string' ? { message: error } : error));
    }

    if (!descriptor || (!includeUnchanged && !dirty)) return [];

    return validateValue(descriptor, this.streams[key](), mode, { values, sent: dirty }).map((message) => ({ message }));
  }

  protected setErrors(key: string, errors: FieldError[]): void {
    this.errors[key] = errors.map((error) => error.message).join('\n');
    this.errorsByKey.set(key, errors);
  }

  /**
   * Validate, then save `data` (default: `buildSaveData()`). Invalid fields reject with a
   * `ValidationError` (messages by key, as `validate()`) and no request, after every visible
   * field's `onclienterrors()` (the structured errors, as `validateErrors()`). 422/403 errors are
   * resolved onto fields by `mapErrors()` and land in `errors` and `fieldErrors()` (`'*'` for
   * unmatched ones) instead of the global alert, then reach every visible field's `onerrors()`; the
   * promise still rejects. Once the record is saved, the changed fields' `afterSave()` run in turn;
   * the first failure rejects, after the others ran.
   */
  async save(data?: SaveData): Promise<M> {
    const invalid = this.validateErrors();

    this.errors = {};
    this.errorsByKey.clear();

    if (Object.keys(invalid).length) {
      Object.entries(invalid).forEach(([key, errors]) => this.setErrors(key, errors));
      this.visibleFields()
        .toArray()
        .forEach((field) => field.onclienterrors?.(invalid, this));
      m.redraw();

      throw new ValidationError(messagesOf(invalid));
    }

    const record = this.model ?? app.store.createRecord<M>(this.definition.type);
    const errorHandler = this.onerror.bind(this);
    // The changes saved by their own request, taken before the reset.
    const after = this.dirtyKeys()
      .filter((key) => this.fields.get(key).afterSave)
      .map((key) => ({ key, field: this.fields.get(key), value: this.streams[key]() }));

    this.saving = true;

    try {
      // A new record resolves to the instance the store now holds, not the one that was saved.
      const saved = (await record.save(data ?? this.buildSaveData(), { errorHandler })) as unknown as M;

      this.model = saved;
      this.reset();

      const failures: unknown[] = [];

      for (const { key, field, value } of after) {
        try {
          await field.afterSave!(saved, value, { form: this, errorHandler });
        } catch (error) {
          // Still a change: saving again retries it.
          this.streams[key]?.(value);
          failures.push(error);
        }
      }

      if (failures.length) throw failures[0];

      return saved;
    } finally {
      this.saving = false;
      m.redraw();
    }
  }

  protected onerror(error: RequestError): void | false {
    const errors = error.response?.errors as JsonApiError[] | undefined;

    if ((error.status === 422 || error.status === 403) && errors?.length) {
      const visible = this.visibleFields();

      const resolved = mapErrors(errors, visible);

      Object.entries(resolved).forEach(([key, keyErrors]) => this.setErrors(key, keyErrors));
      visible.toArray().forEach((field) => field.onerrors?.(resolved, this));

      return;
    }

    return false;
  }
}

/** Whether a form error came from the server (`mapErrors()`), not from client-side validation. */
export function isServerError(error: FieldError): error is ResolvedError {
  return 'error' in error;
}

function messagesOf(errors: Record<string, FieldError[]>): Record<string, string[]> {
  return Object.fromEntries(Object.entries(errors).map(([key, fieldErrors]) => [key, fieldErrors.map((error) => error.message)]));
}
