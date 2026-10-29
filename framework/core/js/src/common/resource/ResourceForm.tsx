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
import type InputFactory from './inputs/InputFactory';
import type ResourceDefinition from './ResourceDefinition';
import ResourceFormState from './ResourceFormState';
import type { Field, FieldContext, InputSpec } from './types';
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
 * A field's error message(s), one line each.
 */
export function renderFieldError(error: string): Mithril.Children {
  return (
    <div className="ResourceForm-error helpText">
      {error.split('\n').map((line) => (
        <div>{line}</div>
      ))}
    </div>
  );
}

/**
 * One visible field of a form state: an input spec through `inputs` (with the required hint), a
 * function input through `input(ctx)`, its error below. Blur validates the field. Shared by
 * `ResourceForm` and modals that lay out the fields themselves.
 */
export function renderFormField<M extends Model>(
  state: ResourceFormState<M>,
  inputs: InputFactory,
  key: string,
  field: Field<M>,
  errorView: (error: string) => Mithril.Children = renderFieldError
): Mithril.Children {
  const error = state.errors[key];
  const descriptor = field.descriptor;
  const className = classList('ResourceForm-field', `ResourceForm-field--${key}`, error && 'has-error');

  if (typeof field.input === 'function') {
    const ctx: FieldContext<M, unknown> = { stream: state.stream(key), model: state.model, error, form: state as ResourceFormState<any> };

    return (
      <div className={classList('Form-group', className)} onfocusout={() => state.validateField(key)}>
        {field.label && <label>{field.label}</label>}
        {field.input(ctx)}
        {error && errorView(error)}
      </div>
    );
  }

  const input = field.input as InputSpec;
  const required = state.isRequired(key);

  return (
    <div className={className} onfocusout={() => state.validateField(key)}>
      {inputs.render(input, {
        name: key,
        stream: state.stream(key),
        label: field.label,
        help: input.help,
        required,
        error,
        descriptor,
        model: state.model,
        form: state as ResourceFormState<any>,
      })}
      {error && errorView(error)}
    </div>
  );
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
 * through `input(ctx)`. Field errors show under their field; unmatched ones above the submit button.
 * Only changed fields are sent.
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
      <form className={classList('ResourceForm', `ResourceForm--${this.attrs.definition.type}`)} onsubmit={this.onsubmit.bind(this)}>
        <Form>{this.items().toArray()}</Form>
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

  errorView(error: string): Mithril.Children {
    return renderFieldError(error);
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
