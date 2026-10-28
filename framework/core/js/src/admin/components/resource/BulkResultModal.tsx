import app from '../../app';
import Button from '../../../common/components/Button';
import Modal, { type IInternalModalAttrs } from '../../../common/components/Modal';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import RequestError from '../../../common/utils/RequestError';
import type { BulkSummary } from '../../../common/resource/BulkRunner';
import type Mithril from 'mithril';
import recordLabel from './recordLabel';

export interface BulkResultModalAttrs<M extends Model = Model> extends IInternalModalAttrs {
  summary: BulkSummary<M>;
}

/**
 * The outcome of a bulk delete (`ResourceDefinition.bulkDelete()`): how many records were deleted,
 * each failed record with its reason, and the selected records it left out.
 */
export default class BulkResultModal<M extends Model = Model, Attrs extends BulkResultModalAttrs<M> = BulkResultModalAttrs<M>> extends Modal<Attrs> {
  className(): string {
    return 'BulkResultModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('core.admin.resource.bulk.result_title');
  }

  content(): Mithril.Children {
    return [
      <div className="Modal-body">{this.bodyItems().toArray()}</div>,
      <div className="Modal-footer">
        <Button className="Button Button--primary BulkResultModal-close" onclick={() => this.hide()}>
          {app.translator.trans('core.admin.resource.bulk.close_button')}
        </Button>
      </div>,
    ];
  }

  /**
   * `deleted`, then `failed` and `skipped` when they have records.
   */
  bodyItems(): ItemList<Mithril.Children> {
    const { ok, failed, skipped } = this.attrs.summary;
    const items = new ItemList<Mithril.Children>();

    items.add(
      'deleted',
      <p className="BulkResultModal-deleted" data-count={ok.length}>
        {app.translator.trans('core.admin.resource.bulk.result_deleted', { count: ok.length })}
      </p>,
      100
    );

    if (failed.length) {
      items.add(
        'failed',
        <div className="BulkResultModal-failed" data-count={failed.length}>
          <p>{app.translator.trans('core.admin.resource.bulk.result_failed', { count: failed.length })}</p>
          <ul>
            {failed.map(({ model, error }) => (
              <li data-id={model.id()}>
                <strong>{recordLabel(model)}</strong>: {this.reason(error)}
              </li>
            ))}
          </ul>
        </div>,
        90
      );
    }

    if (skipped.length) {
      items.add(
        'skipped',
        <div className="BulkResultModal-skipped" data-count={skipped.length}>
          <p>{app.translator.trans('core.admin.resource.bulk.result_skipped', { count: skipped.length })}</p>
          <ul>
            {skipped.map((model) => (
              <li data-id={model.id()}>{recordLabel(model)}</li>
            ))}
          </ul>
        </div>,
        80
      );
    }

    return items;
  }

  /**
   * Why one record failed: the message core's error alert would have shown for the request (the
   * validation details for a 422), else a generic message.
   */
  reason(error: unknown): Mithril.Children {
    if (error instanceof RequestError && error.alert?.content) {
      return error.alert.content as Mithril.Children;
    }

    return app.translator.trans('core.admin.resource.bulk.result_unknown_error');
  }
}
