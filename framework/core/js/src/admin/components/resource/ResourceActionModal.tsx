import app from '../../app';
import Button from '../../../common/components/Button';
import FormModal, { type IFormModalAttrs } from '../../../common/components/FormModal';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import Stream from '../../../common/utils/Stream';
import classList from '../../../common/utils/classList';
import { actionConfirmation, actionLabel, executeAction, partitionByAction, type ResourceAction } from '../../../common/resource/actions';
import type { BulkActionContext, BulkSummary } from '../../../common/resource/BulkRunner';
import type InputFactory from '../../../common/resource/inputs/InputFactory';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import type { Field, FieldContext, InputSpec } from '../../../common/resource/types';
import type Mithril from 'mithril';

export interface ResourceActionModalAttrs<M extends Model = Model> extends IFormModalAttrs {
  definition: ResourceDefinition<M>;
  action: ResourceAction<M>;
  /** The records to run for; the ones the action does not apply to are reported as skipped. */
  models: M[];
  context: BulkActionContext<M>;
  /** Called with the summary once the action ran, or with null when the modal closes without running. */
  ondone?: (summary: BulkSummary<M> | null) => void;
}

/**
 * Confirms a `ResourceAction` with the number of records it applies to, collects its `fields()`
 * (rendered with the definition's form inputs) and runs it through `context.runner`, showing the
 * progress. The outcome replaces the modal (`context.showResult()`, `BulkResultModal` in the
 * table). Focus starts on Cancel when the action is destructive.
 */
export default class ResourceActionModal<
  M extends Model = Model,
  Attrs extends ResourceActionModalAttrs<M> = ResourceActionModalAttrs<M>,
> extends FormModal<Attrs> {
  applicable: M[] = [];
  skipped: M[] = [];
  fields: ItemList<Field<M>> = new ItemList<Field<M>>();
  streams: Record<string, Stream<unknown>> = {};
  progress: { done: number; total: number } | null = null;
  /** The definition's form inputs, built once. */
  inputs!: InputFactory;
  protected settled = false;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const { applicable, skipped } = partitionByAction(this.attrs.action, this.attrs.models);

    this.applicable = applicable;
    this.skipped = skipped;
    this.fields = this.attrs.action.fields?.() ?? new ItemList<Field<M>>();
    this.inputs = this.attrs.definition.formInputs();

    Object.entries(this.fields.toObject()).forEach(([key, item]) => {
      this.streams[key] = Stream((item.content as Field<M>).value?.(null));
    });
  }

  onremove(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onremove(vnode);

    if (!this.settled) this.attrs.ondone?.(null);
  }

  className(): string {
    return classList('ResourceActionModal', `ResourceActionModal--${this.attrs.action.key}`, 'Modal--small');
  }

  title(): Mithril.Children {
    return actionLabel(this.attrs.action, this.applicable.length);
  }

  content(): Mithril.Children {
    return [
      <div className="Modal-body">{this.bodyItems().toArray()}</div>,
      <div className="Modal-footer">
        <div className="ButtonGroup">{this.footerItems().toArray()}</div>
      </div>,
    ];
  }

  /**
   * `confirmation`, `skipped` (when some records are left out), the `fields()` and, while running,
   * `progress`.
   */
  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'confirmation',
      <p className="ResourceActionModal-confirmation">{actionConfirmation(this.attrs.action, this.applicable.length)}</p>,
      100
    );

    if (this.skipped.length) {
      items.add(
        'skipped',
        <p className="ResourceActionModal-skipped helpText">
          {app.translator.trans('core.lib.resource.bulk.confirmation_skipped', { count: this.skipped.length })}
        </p>,
        90
      );
    }

    Object.entries(this.fields.toObject()).forEach(([key, item]) => {
      items.add(`field-${key}`, this.fieldView(key, item.content as Field<M>), 50);
    });

    if (this.progress) {
      items.add(
        'progress',
        <p className="ResourceActionModal-progress" role="status" aria-live="polite">
          {app.translator.trans('core.lib.resource.bulk.progress', this.progress)}
        </p>,
        10
      );
    }

    return items;
  }

  fieldView(key: string, field: Field<M>): Mithril.Children {
    const stream = this.streams[key];

    if (!field.input) return null;

    if (typeof field.input === 'function') {
      return (
        <div className={classList('Form-group', 'ResourceActionModal-field', `ResourceActionModal-field--${key}`)}>
          {field.label && <label>{field.label}</label>}
          {field.input({ stream, model: null } as FieldContext<M, unknown>)}
        </div>
      );
    }

    const input = field.input as InputSpec;

    return (
      <div className={classList('ResourceActionModal-field', `ResourceActionModal-field--${key}`)}>
        {this.inputs.render(input, { name: key, stream, label: field.label, help: input.help, descriptor: field.descriptor })}
      </div>
    );
  }

  /** `cancel` and `submit`. */
  footerItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const action = this.attrs.action;

    items.add(
      'cancel',
      <Button className="Button ResourceActionModal-cancel" onclick={() => this.hide()} disabled={this.loading}>
        {app.translator.trans('core.lib.resource.bulk.cancel_button')}
      </Button>,
      100
    );

    items.add(
      'submit',
      <Button
        type="submit"
        className={classList('Button', action.destructive ? 'Button--danger' : 'Button--primary', 'ResourceActionModal-submit')}
        loading={this.loading}
        disabled={!this.applicable.length}
      >
        {actionLabel(action, this.applicable.length)}
      </Button>,
      90
    );

    return items;
  }

  onready(): void {
    const selector = this.attrs.action.destructive ? '.ResourceActionModal-cancel' : 'input, select, textarea, .ResourceActionModal-submit';

    (this.element as HTMLElement | undefined)?.querySelector?.<HTMLElement>(selector)?.focus();
  }

  /** The values of `fields()`, by key. */
  values(): Record<string, unknown> {
    return Object.fromEntries(Object.entries(this.streams).map(([key, stream]) => [key, stream()]));
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    if (this.loading || !this.applicable.length) return;

    this.loading = true;
    this.progress = { done: 0, total: this.applicable.length };
    // The summary replaces this modal before the run settles.
    this.settled = true;

    executeAction(this.attrs.action, this.applicable, this.skipped, this.values(), this.attrs.context, (done, total) => {
      this.progress = { done, total };
      m.redraw();
    })
      .then((summary) => this.attrs.ondone?.(summary))
      .catch(() => this.attrs.ondone?.(null))
      .finally(() => this.loaded());
  }
}
