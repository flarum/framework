import app from '../app';
import Component, { type ComponentAttrs } from '../Component';
import Button from '../components/Button';
import Form from '../components/Form';
import LoadingIndicator from '../components/LoadingIndicator';
import type Model from '../Model';
import ItemList from '../utils/ItemList';
import classList from '../utils/classList';
import RequestError from '../utils/RequestError';
import type Mithril from 'mithril';
import { constraintsFor } from './descriptor';
import type InputFactory from './inputs/InputFactory';
import type ResourceDefinition from './ResourceDefinition';
import ResourceFormState from './ResourceFormState';
import type { Field, FieldContext, FieldDescriptor, FormMode, InputSpec } from './types';
import { ValidationError } from './validation';

/**
 * Focus the first text field inside `root` (else its first control), unless the user already
 * moved focus into one.
 */
export function focusFirstControl(root: Element | null | undefined): void {
  const active = document.activeElement;

  if (!root?.querySelector || (active && root.contains(active) && active.matches('input, select, textarea'))) return;

  // A field to type into first (a language select or a picker comes before it in many forms),
  // else any control.
  const typable = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=search]):not([disabled]), textarea:not([disabled])';
  const any = 'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])';

  (root.querySelector<HTMLElement>(typable) ?? root.querySelector<HTMLElement>(any))?.focus();
}

/**
 * A field's error message(s), one line each, announced as they appear; `id` is what the field's
 * control points at with `aria-describedby`.
 */
export function renderFieldError(error: string, id?: string): Mithril.Children {
  return (
    <div className="ResourceForm-error helpText" id={id} role="alert">
      {error.split('\n').map((line) => (
        <div>{line}</div>
      ))}
    </div>
  );
}

/**
 * The native input attributes a field's constraints in `mode` imply: `minlength` / `maxlength` for
 * strings, `min` / `max` / `step` for numbers (`step` from `multipleOf`, else 1 for integers and
 * `any` for other numbers). Resource forms are `novalidate`, so these only shape the control (and
 * what assistive technology announces); they never block a submit the server would accept.
 */
export function nativeAttrs(d: FieldDescriptor, mode: FormMode): Record<string, string | number> {
  const c = constraintsFor(d, mode);
  const attrs: Record<string, string | number> = {};

  if (d.kind === 'string') {
    if (c.minLength !== undefined) attrs.minlength = c.minLength;
    if (c.maxLength !== undefined) attrs.maxlength = c.maxLength;
  }

  if (d.kind === 'integer' || d.kind === 'number') {
    if (c.minimum !== undefined) attrs.min = c.minimum;
    if (c.maximum !== undefined) attrs.max = c.maximum;
    attrs.step = c.multipleOf ?? (d.kind === 'integer' ? 1 : 'any');
  }

  return attrs;
}

/**
 * An input spec with the native attributes of its descriptor in `mode` (see `nativeAttrs()`). The
 * spec's own attributes win, except those an inferred input took from the create-mode constraints
 * (or its default `step`), which follow the mode.
 */
function withNativeAttrs(spec: InputSpec, d: FieldDescriptor | undefined, mode: FormMode): InputSpec {
  if (!d) return spec;

  const attrs = nativeAttrs(d, mode);
  const inferred = nativeAttrs(d, 'create');
  const own: InputSpec = { ...spec };

  Object.keys(attrs).forEach((key) => {
    if (own[key] !== undefined && (own[key] === inferred[key] || (key === 'step' && own.step === (d.kind === 'integer' ? 1 : 'any')))) {
      delete own[key];
    }
  });

  return { ...attrs, ...own };
}

/**
 * One visible field of a form state: an input spec through `inputs` (with the required hint, the
 * native attributes of its constraints and `ui.help`), a function input through `input(ctx)`, its
 * error below. `ui.markRequired` marks the field required without checking it. Blur validates the
 * field. Shared by `ResourceForm` and modals that lay out the fields themselves.
 */
export function renderFormField<M extends Model>(
  state: ResourceFormState<M>,
  inputs: InputFactory,
  key: string,
  field: Field<M>,
  errorView: (error: string, id?: string) => Mithril.Children = renderFieldError
): Mithril.Children {
  const error = state.errors[key];
  const errorId = `${state.domId}-${key}-error`;
  const descriptor = field.descriptor;
  const required = state.isRequired(key) || !!descriptor?.ui?.markRequired;
  const help = descriptor?.ui?.help;
  const className = classList('ResourceForm-field', `ResourceForm-field--${key}`, required && 'ResourceForm-field--required', error && 'has-error');

  if (typeof field.input === 'function') {
    const ctx: FieldContext<M, unknown> = { stream: state.stream(key), model: state.model, error, form: state as ResourceFormState<any> };

    return (
      <div className={classList('Form-group', className)} onfocusout={() => state.validateField(key)}>
        {field.label && <label>{field.label}</label>}
        {help && <div className="helpText">{help}</div>}
        {field.input(ctx)}
        {error && errorView(error, errorId)}
      </div>
    );
  }

  const input = withNativeAttrs(field.input as InputSpec, descriptor, state.mode());

  return (
    <div className={className} onfocusout={() => state.validateField(key)}>
      {inputs.render(input, {
        name: key,
        stream: state.stream(key),
        label: field.label,
        help: input.help ?? help,
        required,
        error,
        errorId,
        descriptor,
        model: state.model,
        form: state as ResourceFormState<any>,
      })}
      {error && errorView(error, errorId)}
    </div>
  );
}

/**
 * Lay out a form's items (as `ResourceForm.items()` / `ResourceFormModal.fields()` build them):
 * the fields with a `ui.group` move into one fieldset per group, legend the group, in the order the
 * groups first appear. The fieldsets follow the other fields and come before the items with a
 * negative priority (errors, submit).
 */
export function groupFormItems<M extends Model>(state: ResourceFormState<M>, items: ItemList<Mithril.Children>): Mithril.Children[] {
  const entries = Object.entries(items.toObject()).sort(([, a], [, b]) => b.priority - a.priority);
  const visible = state.visibleFields();
  const groups = new Map<string, Mithril.Children[]>();
  const before: Mithril.Children[] = [];
  const after: Mithril.Children[] = [];

  entries.forEach(([key, item]) => {
    const group = visible.has(key) ? visible.get(key).descriptor?.ui?.group : undefined;

    if (typeof group === 'string' && group !== '') {
      if (!groups.has(group)) groups.set(group, []);
      groups.get(group)!.push(item.content as Mithril.Children);
    } else {
      (item.priority < 0 ? after : before).push(item.content as Mithril.Children);
    }
  });

  const fieldsets = [...groups].map(([legend, children]) => (
    <fieldset className="ResourceForm-group FieldSet">
      <legend className="FieldSet-label">{legend}</legend>
      <div className="FieldSet-items">{children}</div>
    </fieldset>
  ));

  return [...before, ...fieldsets, ...after];
}

export interface ResourceFormAttrs<M extends Model> extends ComponentAttrs {
  definition: ResourceDefinition<M>;
  /** The record to edit; omit or null to create one. */
  model?: M | null;
  /** Called after a successful save with the saved record. */
  onsaved?: (model: M) => void;
  /** Called once the state is ready, e.g. for a modal that wants to drive it. */
  onstate?: (state: ResourceFormState<M>) => void;
  /** Render the submit button. Default true. */
  submit?: boolean;
  /**
   * Focus the first control once the form has rendered, unless focus is already in it. In a
   * modal, core's focus trap keeps a focused element inside the dialog.
   */
  autofocus?: boolean;
  /** The input factory for this form; default `definition.formInputs()`. */
  inputs?: InputFactory;
}

/**
 * An inline form for one record. Loads the descriptor, then renders every visible field: input specs
 * through the form's `InputFactory` (with required/maxlength/min/max/options hints), function inputs
 * through `input(ctx)`, grouped fields in fieldsets (see `groupFormItems()`). Field errors show under
 * their field; unmatched ones above the submit button. Only changed fields are sent. The form is
 * `novalidate`: the native attributes guide the user, validation is the form state's.
 */
export default class ResourceForm<M extends Model = Model, Attrs extends ResourceFormAttrs<M> = ResourceFormAttrs<M>> extends Component<Attrs> {
  formState: ResourceFormState<M> | null = null;
  loadError: boolean = false;
  protected focused: boolean = false;
  /** The rendered form; see `onupdate()`. */
  protected root: Element | null = null;
  inputs!: InputFactory;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    this.inputs = this.attrs.inputs ?? this.attrs.definition.formInputs();

    ResourceFormState.create<M>(this.attrs.definition, this.attrs.model ?? null).then(
      (state) => {
        this.formState = state;
        this.attrs.onstate?.(state);
        m.redraw();
      },
      () => {
        this.loadError = true;
        m.redraw();
      }
    );
  }

  onupdate(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onupdate(vnode);

    this.root = vnode.dom;

    if (this.attrs.autofocus && this.formState && !this.focused) {
      this.focused = true;
      // `vnode.dom`, not `this.element`: core's Component sets that in oncreate only, where this
      // component still rendered its loading indicator.
      this.autofocus(vnode.dom);
    }
  }

  /**
   * Focus the form's first control; override to focus another one.
   */
  autofocus(root: Element): void {
    focusFirstControl(root);
  }

  view(): Mithril.Children {
    if (this.loadError) {
      return <div className="ResourceForm ResourceForm--error">{app.translator.trans('core.lib.resource.form.load_error')}</div>;
    }

    if (!this.formState) {
      return <LoadingIndicator />;
    }

    return (
      <form className={classList('ResourceForm', `ResourceForm--${this.attrs.definition.type}`)} novalidate onsubmit={this.onsubmit.bind(this)}>
        <Form>{groupFormItems(this.formState, this.items())}</Form>
      </form>
    );
  }

  items(): ItemList<Mithril.Children> {
    const state = this.formState!;
    const items = new ItemList<Mithril.Children>();

    Object.entries(state.visibleFields().toObject()).forEach(([key, item]) => {
      items.add(key, this.fieldView(key, item.content as Field<M>), item.priority);
    });

    if (state.errors['*']) {
      items.add('errors', <div className="ResourceForm-errors Form-group">{this.errorView(state.errors['*'])}</div>, -90);
    }

    if (this.attrs.submit ?? true) {
      items.add(
        'submit',
        <div className="Form-group ResourceForm-submit">
          <Button type="submit" className="Button Button--primary" loading={state.saving} disabled={!state.dirtyKeys().length}>
            {app.translator.trans('core.lib.resource.form.submit_button')}
          </Button>
        </div>,
        -100
      );
    }

    return items;
  }

  fieldView(key: string, field: Field<M>): Mithril.Children {
    return renderFormField(this.formState!, this.inputs, key, field, this.errorView.bind(this));
  }

  /**
   * Focus the first field with an error, unless focus is already in it.
   */
  focusFirstInvalid(): void {
    focusFirstControl(this.root?.querySelector('.ResourceForm-field.has-error'));
  }

  errorView(error: string, id?: string): Mithril.Children {
    return renderFieldError(error, id);
  }

  async onsubmit(event: Event): Promise<void> {
    event.preventDefault();

    const state = this.formState;

    if (!state || state.saving) return;

    try {
      const model = await state.save();
      this.attrs.onsaved?.(model);
    } catch (error) {
      if (error instanceof ValidationError) {
        m.redraw.sync();
        this.focusFirstInvalid();

        return;
      }

      // Field errors are in `state.errors`; other request errors got core's alert.
      if (!(error instanceof RequestError)) throw error;
    }
  }
}
