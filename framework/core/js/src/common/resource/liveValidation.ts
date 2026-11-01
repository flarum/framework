import app from '../app';
import fireDebugWarning from '../helpers/fireDebugWarning';
import type Model from '../Model';
import type RequestError from '../utils/RequestError';
import { mapErrors } from './errors';
import { fieldSave } from './field';
import type ResourceFormState from './ResourceFormState';
import type { JsonApiError, SaveData } from './types';

/** How long typing pauses before a touched field is validated again. */
export const LIVE_VALIDATE_DELAY = 600;

const identifier = (model: Model) => ({ type: model.data.type, id: model.id()! });

/** The types whose server cannot answer a precognitive request; their forms skip live checks. */
const unsupported = new Set<string>();

/**
 * Forget which types had live validation turned off. For tests.
 *
 * @internal
 */
export function resetLiveValidation(): void {
  unsupported.clear();
}

/** Whether the server validated without saving: 204, or a `Precognition-Success: true` header. */
function precognitiveSuccess(xhr: XMLHttpRequest | null): boolean {
  return !!xhr && (xhr.status === 204 || xhr.getResponseHeader?.('Precognition-Success') === 'true');
}

function notSupported(error: RequestError): boolean {
  return error?.status === 400 && ((error.response?.errors ?? []) as JsonApiError[]).some((e) => e.code === 'precognition_not_supported');
}

/**
 * The JSON:API document `Model.save()` would send for `data`, without touching the record:
 * `relationships` become identifiers, a null relationship is left out.
 */
export function saveDocument(type: string, id: string | undefined, data: SaveData): Record<string, unknown> {
  const { relationships, ...attributes } = data;
  const resource: Record<string, unknown> = { type, attributes };

  if (id !== undefined) resource.id = id;

  if (relationships) {
    resource.relationships = Object.fromEntries(
      Object.entries(relationships)
        .filter(([, related]) => related !== null)
        .map(([name, related]) => [name, { data: Array.isArray(related) ? related.map(identifier) : identifier(related as Model) }])
    );
  }

  return { data: resource };
}

/**
 * Server-side checks of single fields a form cannot check itself (`ui.liveValidate`): the save
 * request of the form's mode, with only that field and the `Precognition` headers, so the server
 * validates the field without saving. A precognitive success (204, or `Precognition-Success: true`)
 * clears the field's server error, 422 shows it. Another 2xx, or 400 `precognition_not_supported`,
 * means the server did not validate precognitively: live checks stop for the type (with a debug
 * warning for the 2xx). Any other answer is ignored. A newer check of a field supersedes the one in
 * flight.
 */
export default class LiveValidator {
  /** Fields the user has left once; from then on, a change is checked after a pause. */
  protected touched = new Set<string>();
  protected timers: Record<string, ReturnType<typeof setTimeout>> = {};
  /** The sequence number of each field's latest check; an answer to an older one is dropped. */
  protected sequence: Record<string, number> = {};
  protected requests: Record<string, XMLHttpRequest> = {};

  constructor(protected readonly form: ResourceFormState<any>) {}

  /**
   * Whether the field is checked by the server.
   */
  handles(key: string): boolean {
    return !unsupported.has(this.form.definition.type) && this.form.fields.has(key) && !!this.form.fields.get(key).descriptor?.ui?.liveValidate;
  }

  /**
   * The value of a touched field changed: check it once typing pauses.
   */
  changed(key: string): void {
    if (!this.touched.has(key) || !this.handles(key)) return;

    clearTimeout(this.timers[key]);
    this.timers[key] = setTimeout(() => this.validate(key), LIVE_VALIDATE_DELAY);
  }

  /**
   * Check the field now, e.g. on blur. Resolves once the answer is applied (or dropped).
   */
  validate(key: string): Promise<void> {
    if (!this.handles(key)) return Promise.resolve();

    this.touched.add(key);
    clearTimeout(this.timers[key]);
    this.requests[key]?.abort();
    delete this.requests[key];

    const sequence = (this.sequence[key] ?? 0) + 1;
    this.sequence[key] = sequence;

    const form = this.form;
    const field = form.fields.get(key);

    // An unchanged field is not sent on update, so the server would not validate it either.
    if (!(key in form.streams) || (!form.isCreating() && !form.isDirty(key))) {
      this.apply(key, null);

      return Promise.resolve();
    }

    const endpoint = form.definition.descriptor?.endpoint ?? `/${form.definition.type}`;
    const id = form.isCreating() ? undefined : form.model!.id();
    const data = fieldSave(key, field, form.stream(key)(), form.initial[key]);
    let request: XMLHttpRequest | null = null;

    return app
      .request<null>({
        method: id === undefined ? 'POST' : 'PATCH',
        url: app.forum.attribute('apiUrl') + endpoint + (id === undefined ? '' : `/${id}`),
        body: saveDocument(form.definition.type, id, data),
        headers: { Precognition: 'true', 'Precognition-Validate-Only': field.descriptor?.name ?? key },
        config: (xhr: XMLHttpRequest) => {
          request = this.requests[key] = xhr;
        },
        errorHandler: () => {},
      })
      .then(
        () => {
          if (this.sequence[key] !== sequence) return;

          if (precognitiveSuccess(request)) this.apply(key, null);
          else this.disable(true);
        },
        (error: RequestError) => {
          if (this.sequence[key] !== sequence) return;
          if (notSupported(error)) return this.disable();
          if (error?.status !== 422) return;

          const errors = (error.response?.errors ?? []) as JsonApiError[];

          this.apply(key, mapErrors(errors, form.visibleFields())[key] ?? null);
        }
      )
      .finally(() => {
        if (this.sequence[key] === sequence) delete this.requests[key];
      });
  }

  /**
   * Stop every pending and running check, e.g. when the form is reset.
   */
  cancel(): void {
    Object.values(this.timers).forEach((timer) => clearTimeout(timer));
    Object.values(this.requests).forEach((xhr) => xhr.abort());
    Object.keys(this.sequence).forEach((key) => this.sequence[key]++);
    this.timers = {};
    this.requests = {};
    this.touched.clear();
  }

  /** Stop live checks for the form's type, see the class; `warn` once per type. */
  protected disable(warn: boolean = false): void {
    const type = this.form.definition.type;

    if (warn && !unsupported.has(type)) {
      fireDebugWarning(
        `LiveValidator(${type}): a precognitive request was answered without Precognition-Success; live validation is off for the type.`
      );
    }

    unsupported.add(type);
    this.cancel();
  }

  protected apply(key: string, error: string | null): void {
    this.form.setServerError(key, error);
    m.redraw();
  }
}
