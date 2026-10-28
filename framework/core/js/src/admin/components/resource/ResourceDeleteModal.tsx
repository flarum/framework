import app from '../../app';
import Button from '../../../common/components/Button';
import FormModal, { type IFormModalAttrs } from '../../../common/components/FormModal';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import type Mithril from 'mithril';
import recordLabel from './recordLabel';

export interface ResourceDeleteModalAttrs<M extends Model = Model> extends IFormModalAttrs {
  definition: ResourceDefinition<M>;
  model: M;
  /** Called with the deleted record before the modal closes. */
  ondeleted?: (model: M) => void;
}

/**
 * Confirm and delete one record through the type's `Delete` endpoint (an extension's replacement,
 * such as gdpr's, included). Focus starts on Cancel, the safe choice; errors show as the modal's
 * alert.
 */
export default class ResourceDeleteModal<
  M extends Model = Model,
  Attrs extends ResourceDeleteModalAttrs<M> = ResourceDeleteModalAttrs<M>,
> extends FormModal<Attrs> {
  className(): string {
    return `ResourceDeleteModal ResourceDeleteModal--${this.attrs.definition.type} Modal--small`;
  }

  title(): Mithril.Children {
    return app.translator.trans('core.admin.resource.delete.title');
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
   * The modal body: the `confirmation` naming the record.
   */
  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'confirmation',
      <p className="ResourceDeleteModal-confirmation">
        {app.translator.trans('core.admin.resource.delete.confirmation', { name: <strong>{recordLabel(this.attrs.model)}</strong> })}
      </p>,
      100
    );

    return items;
  }

  /**
   * The footer buttons: `cancel` and `submit`.
   */
  footerItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'cancel',
      <Button className="Button ResourceDeleteModal-cancel" onclick={() => this.hide()} disabled={this.loading}>
        {app.translator.trans('core.admin.resource.delete.cancel_button')}
      </Button>,
      100
    );

    items.add(
      'submit',
      <Button type="submit" className="Button Button--danger ResourceDeleteModal-submit" loading={this.loading}>
        {app.translator.trans('core.admin.resource.delete.submit_button')}
      </Button>,
      90
    );

    return items;
  }

  onready(): void {
    (this.element as HTMLElement | undefined)?.querySelector?.<HTMLElement>('.ResourceDeleteModal-cancel')?.focus();
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;
    this.alertAttrs = null;

    const model = this.attrs.model;

    model
      .delete({}, { errorHandler: this.onerror.bind(this) })
      .then(() => {
        this.attrs.ondeleted?.(model);
        this.hide();
      })
      .catch(() => {})
      .finally(() => this.loaded());
  }
}
