import app from '../app';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import type RequestError from '../utils/RequestError';
import Stream from '../utils/Stream';
import { isWritable } from './descriptor';
import { mapErrors } from './errors';
import { fieldSave, fieldValue, mergeSaveData, snapshot } from './field';
import type ResourceDefinition from './ResourceDefinition';
import type { Field, FormMode, JsonApiError, SaveData } from './types';
import { ValidationError, validateValue } from './validation';

/**
 * The editing state of one record: a stream per visible field, the values it started with, shared
 * view state and the field errors. Only changed fields are sent, because the server answers 403 for
 * any non-writable field present in the payload.
 *
 * @experimental
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

  /**
   * Await the definition's descriptor, then build the state.
   */
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
   * Fields with an input, passing `when()`, and (when inferred) writable in the current mode.
   */
  visibleFields(): ItemList<Field<M>> {
    const visible = new ItemList<Field<M>>();
    const mode = this.mode();

    Object.entries(this.fields.toObject()).forEach(([key, item]) => {
      const field = item.content as Field<M>;

      if (!field.input) return;
      if (field.when && !field.when(this.model)) return;
      if (field.descriptor && !isWritable(field.descriptor, mode)) return;

      visible.add(key, field, item.priority);
    });

    return visible;
  }

  /**
   * (Re)initialize the streams and snapshots from the current model. A new record's empty field
   * starts from the descriptor default, unless the field has its own `value()`. Being the initial
   * value, an unchanged default is not sent; the Create endpoint fills in the same.
   */
  reset(): void {
    this.streams = {};
    this.initial = {};
    this.errors = {};
    this.clientErrorKeys.clear();

    const creating = this.isCreating();

    Object.entries(this.visibleFields().toObject()).forEach(([key, item]) => {
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

  stream<V = unknown>(key: string): Stream<V> {
    return this.streams[key] as Stream<V>;
  }

  isDirty(key: string): boolean {
    return key in this.streams && snapshot(this.streams[key]()) !== snapshot(this.initial[key]);
  }

  dirtyKeys(): string[] {
    return Object.keys(this.streams).filter((key) => this.isDirty(key));
  }

  /**
   * The changed fields' save data, deep-merged.
   */
  buildSaveData(): SaveData {
    return mergeSaveData(...this.dirtyKeys().map((key) => fieldSave(key, this.fields.get(key), this.streams[key](), this.initial[key])));
  }

  /**
   * Check the visible fields against their descriptors: every field while creating, the changed
   * ones while updating (only those are sent). Fields without a descriptor and `conditional` ones
   * are left to the server.
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
   * Check one changed field, e.g. on blur, and show or clear its client-side error. Never blocks.
   */
  validateField(key: string): string[] {
    if (!this.fields.has(key)) return [];

    const messages = this.check(key, this.fields.get(key), false);

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

    if (!descriptor || descriptor.conditional || !(key in this.streams)) return [];
    if (!includeUnchanged && !this.isDirty(key)) return [];

    return validateValue(descriptor, this.streams[key](), this.mode());
  }

  /**
   * Validate, then save `data` (default: the changed fields, `buildSaveData()`). Invalid fields reject with a `ValidationError` and no
   * request; their messages land in `errors` like the server's. 422/403 field errors land in
   * `errors` (keyed by field, `'*'` for the rest) instead of the global alert; the promise still
   * rejects.
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
