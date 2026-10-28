import app from '../../app';
import Button from '../../../common/components/Button';
import Form from '../../../common/components/Form';
import FormModal, { type IFormModalAttrs } from '../../../common/components/FormModal';
import LoadingIndicator from '../../../common/components/LoadingIndicator';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import RequestError from '../../../common/utils/RequestError';
import classList from '../../../common/utils/classList';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import { focusFirstControl, renderFieldError, renderFormField } from '../../../common/resource/ResourceForm';
import ResourceFormState from '../../../common/resource/ResourceFormState';
import type { Field, SaveData } from '../../../common/resource/types';
import { ValidationError } from '../../../common/resource/validation';
import type Mithril from 'mithril';
import recordLabel from './recordLabel';

/**
 * @experimental
 */
export interface ResourceFormModalAttrs<M extends Model = Model> extends IFormModalAttrs {
  definition: ResourceDefinition<M>;
  /** The record to edit; omit or null to create one. */
  model?: M | null;
  /** Called with the saved record before the modal closes. */
  onsaved?: (model: M) => void;
  /** The input factory for the fields; default `definition.formInputs()`. */
  inputs?: InputFactory;
}

/**
 * Create or edit one record in a modal, with the fields the definition infers from the descriptor.
 *
 * Its overridable surface mirrors `CreateUserModal` / `EditUserModal`, so an extension ports by
 * renaming the base class: `fields()` is the ItemList of form groups (keyed by field name, plus
 * `submit`), `data()` the save data and `submitData()` what is sent. Only changed fields are sent,
 * because the server answers 403 for a non-writable field present in the payload. Fields are
 * validated against the descriptor before the request; 422/403 field errors show under their
 * field.
 *
 * Not wired into any page in 2.0.
 *
 * @experimental
 */
export default class ResourceFormModal<
  M extends Model = Model,
  Attrs extends ResourceFormModalAttrs<M> = ResourceFormModalAttrs<M>,
> extends FormModal<Attrs> {
  /**
   * The form's state; null until the descriptor is loaded.
   *
   * @experimental
   */
  formState: ResourceFormState<M> | null = null;

  /**
   * @experimental
   */
  loadError: boolean = false;

  /**
   * @experimental
   */
  inputs!: InputFactory;

  protected focused: boolean = false;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    this.inputs = this.attrs.inputs ?? this.attrs.definition.formInputs();

    ResourceFormState.create<M>(this.attrs.definition, this.attrs.model ?? null).then(
      (state) => {
        this.formState = state;
        m.redraw();
      },
      (error) => {
        console.error(`ResourceFormModal(${this.attrs.definition.type}):`, error);
        this.loadError = true;
        m.redraw();
      }
    );
  }

  onupdate(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onupdate(vnode);

    // The fields arrive after `onready()`; focus the first one once they have rendered.
    if (this.formState && !this.focused) {
      this.focused = true;
      focusFirstControl(vnode.dom.querySelector?.('.ResourceForm'));
    }
  }

  className(): string {
    return `ResourceFormModal ResourceFormModal--${this.attrs.definition.type} Modal--small`;
  }

  title(): Mithril.Children {
    const model = this.attrs.model;

    return model?.exists
      ? app.translator.trans('core.admin.resource.form.edit_title', { name: recordLabel(model) })
      : app.translator.trans('core.admin.resource.form.create_title');
  }

  content(): Mithril.Children {
    return <div className="Modal-body">{this.body()}</div>;
  }

  /**
   * @experimental
   */
  body(): Mithril.Children {
    if (this.loadError) {
      return <div className="ResourceForm ResourceForm--error">{app.translator.trans('core.lib.resource.form.load_error')}</div>;
    }

    if (!this.formState) {
      return <LoadingIndicator />;
    }

    return (
      <div className={classList('ResourceForm', `ResourceForm--${this.attrs.definition.type}`)}>
        <Form>{this.fields().toArray()}</Form>
      </div>
    );
  }

  /**
   * One item per field the actor may write in the current mode, keyed by field name, then the
   * unmatched server errors (`errors`) and the `submit` button. Empty while loading.
   *
   * @experimental
   */
  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const state = this.formState;

    if (!state) return items;

    Object.entries(state.visibleFields().toObject()).forEach(([key, item]) => {
      items.add(key, this.fieldView(key, item.content as Field<M>), item.priority);
    });

    if (state.errors['*']) {
      items.add('errors', <div className="ResourceForm-errors Form-group">{renderFieldError(state.errors['*'])}</div>, -5);
    }

    items.add(
      'submit',
      <div className="Form-group Form-controls">
        <Button className="Button Button--primary" type="submit" loading={this.loading} disabled={!state.dirtyKeys().length}>
          {app.translator.trans('core.lib.resource.form.submit_button')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  /**
   * Render one field: its input, its error below.
   *
   * @experimental
   */
  fieldView(key: string, field: Field<M>): Mithril.Children {
    return renderFormField(this.formState!, this.inputs, key, field);
  }

  /**
   * The save data of the changed fields.
   *
   * @experimental
   */
  data(): SaveData {
    return this.formState?.buildSaveData() ?? {};
  }

  /**
   * What the submit sends; `data()` by default.
   *
   * @experimental
   */
  submitData(): SaveData {
    return this.data();
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const state = this.formState;

    if (!state || this.loading) return;

    this.loading = true;
    this.alertAttrs = null;

    state
      .save(this.submitData())
      .then((model) => this.onsaved(model))
      .catch((error) => {
        if (error instanceof ValidationError) {
          m.redraw.sync();
          focusFirstControl(this.element?.querySelector('.ResourceForm-field.has-error'));

          return;
        }

        // Field errors are in `formState.errors`; other request errors got core's alert.
        if (!(error instanceof RequestError)) throw error;
      })
      .finally(() => this.loaded());
  }

  /**
   * Called with the saved record: passes it to the `onsaved` attr, then closes.
   *
   * @experimental
   */
  onsaved(model: M): void {
    this.attrs.onsaved?.(model);
    this.hide();
  }
}
