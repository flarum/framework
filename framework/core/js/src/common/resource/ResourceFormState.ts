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
import type { Field, FormMode, JsonApiError, SaveData } from './types';
import { ValidationError, validateValue } from './validation';

/**
 * The editing state of one record. Only changed fields are sent: the server answers 403 for any
 * non-writable field in the payload.
 */
export default class ResourceFormState<M extends Model = Model> {
  /** The record being edited; null while creating. Replaced by the saved record. */
  model: M | null;
  fields: ItemList<Field<M>>;
  streams: Record<string, Stream<unknown>> = {};
  initial: Record<string, unknown> = {};
  errors: Record<string, string> = {};
  saving: boolean = false;
  /** Keys whose entry in `errors` came from client-side validation, not the server. */
  protected clientErrorKeys = new Set<string>();
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
    this.clientErrorKeys.clear();

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

  isDirty(key: string): boolean {
    return key in this.streams && snapshot(this.streams[key]()) !== snapshot(this.initial[key]);
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
   * Every shown field while creating, the changed ones while updating. Fields without a descriptor
   * and `conditional` ones are left to the server.
   */
  validate(): Record<string, string[]> {
    const errors: Record<string, string[]> = {};

    Object.entries(this.visibleFields().toObject()).forEach(([key, item]) => {
      const messages = this.check(key, item.content as Field<M>, this.isCreating());

      if (messages.length) errors[key] = messages;
    });

    return errors;
  }

  /**
   * Show or clear one field's client-side error, e.g. on blur. Other client-side errors are
   * rechecked, as a value can make a sibling (not) required.
   */
  validateField(key: string): string[] {
    if (!this.fields.has(key)) return [];

    const messages = this.check(key, this.fields.get(key), false);

    this.clientErrorKeys.forEach((other) => {
      if (other === key || !this.fields.has(other)) return;
      if (this.check(other, this.fields.get(other), this.isCreating()).length) return;

      delete this.errors[other];
      this.clientErrorKeys.delete(other);
    });

    if (messages.length) {
      this.errors[key] = messages.join('\n');
      this.clientErrorKeys.add(key);
    } else if (this.clientErrorKeys.has(key)) {
      delete this.errors[key];
      this.clientErrorKeys.delete(key);
    }

    return messages;
  }

  protected check(key: string, field: Field<M>, includeUnchanged: boolean): string[] {
    const descriptor = field.descriptor;

    if (!descriptor || descriptor.conditional || !this.isVisible(key)) return [];
    if (!includeUnchanged && !this.isDirty(key)) return [];

    return validateValue(descriptor, this.streams[key](), this.mode(), { values: this.values(), sent: this.isDirty(key) });
  }

  /**
   * Validate, then save `data` (default: `buildSaveData()`). Invalid fields reject with a
   * `ValidationError` and no request. 422/403 field errors land in `errors` (`'*'` for unmatched
   * ones) instead of the global alert; the promise still rejects.
   */
  async save(data?: SaveData): Promise<M> {
    const invalid = this.validate();

    this.errors = {};
    this.clientErrorKeys.clear();

    if (Object.keys(invalid).length) {
      Object.entries(invalid).forEach(([key, messages]) => {
        this.errors[key] = messages.join('\n');
        this.clientErrorKeys.add(key);
      });
      m.redraw();

      throw new ValidationError(invalid);
    }

    const record = this.model ?? app.store.createRecord<M>(this.definition.type);

    this.saving = true;

    try {
      // A new record resolves to the instance the store now holds, not the one that was saved.
      const saved = (await record.save(data ?? this.buildSaveData(), { errorHandler: this.onerror.bind(this) })) as unknown as M;

      this.model = saved;
      this.reset();

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

      this.errors = mapErrors(errors, visible);
      visible.toArray().forEach((field) => field.onerrors?.(errors, this));

      return;
    }

    return false;
  }
}
