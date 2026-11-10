import app from '../../app';
import Button from '../../../common/components/Button';
import Modal, { type IInternalModalAttrs } from '../../../common/components/Modal';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import RequestError from '../../../common/utils/RequestError';
import { actionLabel, type ResourceAction } from '../../../common/resource/actions';
import type { BulkSummary } from '../../../common/resource/BulkRunner';
import type Mithril from 'mithril';
import type ResourceDefinition from '../../../common/resource/ResourceDefinition';
import recordLabel from '../../../common/resource/recordLabel';

export interface BulkResultModalAttrs<M extends Model = Model> extends IInternalModalAttrs {
  summary: BulkSummary<M>;
  /** Names the records through `definition.recordLabel()`; without it, the default label. */
  definition?: ResourceDefinition<M>;
  /** The action that ran; without it (or for the built-in `delete`), the summary speaks of deleting. */
  action?: ResourceAction<M>;
}

/**
 * One line of failures: the records that failed for the same reason.
 */
export interface FailureGroup<M> {
  /** The JSON:API error `code`, else its `detail`, else `unknown`. */
  key: string;
  reason: Mithril.Children;
  models: M[];
}

/**
 * The outcome of a `ResourceAction` over several records: how many it processed, the failed ones
 * grouped by reason ("N not processed: reason", each with the list of records) and the records it
 * left out.
 */
export default class BulkResultModal<M extends Model = Model, Attrs extends BulkResultModalAttrs<M> = BulkResultModalAttrs<M>> extends Modal<Attrs> {
  className(): string {
    return 'BulkResultModal Modal--small';
  }

  /** Whether the summary is of a delete (no action, or the built-in `delete`). */
  isDelete(): boolean {
    return !this.attrs.action || this.attrs.action.key === 'delete';
  }

  title(): Mithril.Children {
    if (!this.isDelete()) return actionLabel(this.attrs.action!, this.attrs.summary.ok.length);

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

  label(model: M): string {
    return this.attrs.definition ? this.attrs.definition.recordLabel(model) : recordLabel(model);
  }

  /**
   * `deleted` (the processed count), then `failed` (one line per `failureGroups()` entry) and
   * `skipped` when they have records.
   */
  bodyItems(): ItemList<Mithril.Children> {
    const { ok, failed, skipped } = this.attrs.summary;
    const items = new ItemList<Mithril.Children>();
    const remove = this.isDelete();

    items.add(
      'deleted',
      <p className="BulkResultModal-deleted" data-count={ok.length}>
        {remove
          ? app.translator.trans('core.admin.resource.bulk.result_deleted', { count: ok.length })
          : app.translator.trans('core.lib.resource.bulk.result_processed', { count: ok.length })}
      </p>,
      100
    );

    if (failed.length) {
      items.add(
        'failed',
        <div className="BulkResultModal-failed" data-count={failed.length}>
          {this.failureGroups().map((group) =>
            this.groupView(
              group.models,
              app.translator.trans('core.lib.resource.bulk.result_failed_group', { count: group.models.length, reason: group.reason }),
              { 'data-key': group.key }
            )
          )}
        </div>,
        90
      );
    }

    if (skipped.length) {
      items.add(
        'skipped',
        <div className="BulkResultModal-skipped" data-count={skipped.length}>
          {this.groupView(
            skipped,
            remove
              ? app.translator.trans('core.admin.resource.bulk.result_skipped', { count: skipped.length })
              : app.translator.trans('core.lib.resource.bulk.result_skipped', { count: skipped.length })
          )}
        </div>,
        80
      );
    }

    return items;
  }

  /** One group: its line, expanding to the records' labels. */
  groupView(models: M[], summary: Mithril.Children, attrs: Record<string, unknown> = {}): Mithril.Children {
    return (
      <details className="BulkResultModal-group" data-count={models.length} {...attrs}>
        <summary>{summary}</summary>
        <ul>
          {models.map((model) => (
            <li data-id={model.id()}>{this.label(model)}</li>
          ))}
        </ul>
      </details>
    );
  }

  /**
   * The failed records grouped by the first JSON:API error's `code`, else its `detail`, else
   * `unknown`, in order of first appearance.
   */
  failureGroups(): FailureGroup<M>[] {
    const groups = new Map<string, FailureGroup<M>>();

    this.attrs.summary.failed.forEach(({ model, error }) => {
      const first = error instanceof RequestError ? error.response?.errors?.[0] : undefined;
      const key = first?.code || first?.detail || 'unknown';
      const group = groups.get(key);

      if (group) group.models.push(model);
      else groups.set(key, { key, reason: this.reason(error), models: [model] });
    });

    return [...groups.values()];
  }

  /**
   * Why a record failed: the first JSON:API error's `detail`, else the message core's error alert
   * would have shown for the request, else a generic message.
   */
  reason(error: unknown): Mithril.Children {
    if (error instanceof RequestError) {
      const detail = error.response?.errors?.[0]?.detail;

      if (detail) return detail;
      if (error.alert?.content) return error.alert.content as Mithril.Children;
    }

    return app.translator.trans('core.admin.resource.bulk.result_unknown_error');
  }
}
