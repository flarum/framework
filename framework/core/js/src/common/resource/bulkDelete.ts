import type Mithril from 'mithril';
import app from '../app';
import Button from '../components/Button';
import type Model from '../Model';
import extractText from '../utils/extractText';
import type { BulkActionContext, BulkSummary } from './BulkRunner';
import type ResourceListState from './ResourceListState';

/** The lists a bulk delete is running for, so a second click is ignored. */
const running = new WeakSet<ResourceListState<any>>();

/**
 * A "Delete selected" button for a definition's `bulkActions()`. It confirms with the count, leaves
 * out rows whose `canDelete` is false, deletes the others one request per record through
 * `context.runner` (so an extension's replaced `Delete` endpoint runs per record), shows the summary
 * through `context.showResult()` and then refreshes the list.
 */
export function bulkDeleteAction<M extends Model>(context: BulkActionContext<M>): Mithril.Children {
  const count = context.list.selectedModels().length;

  return m(
    Button,
    {
      className: 'Button Button--danger ResourceTable-bulkDelete',
      icon: 'fas fa-trash-alt',
      disabled: !count,
      loading: running.has(context.list),
      // The summary reports the deletes; a failed reload has already shown its error.
      onclick: () => runBulkDelete(context).catch(() => {}),
    },
    app.translator.trans('core.lib.resource.bulk.delete_button', { count })
  );
}

/**
 * The work behind `bulkDeleteAction()`. Resolves to the summary, or null when there was nothing to
 * do, the user cancelled or a delete is already running for the list.
 */
export async function runBulkDelete<M extends Model>(context: BulkActionContext<M>): Promise<BulkSummary<M> | null> {
  const { list } = context;

  if (running.has(list)) return null;

  const selected = list.selectedModels();
  const deletable = selected.filter((model) => !!model.attribute('canDelete'));
  const skipped = selected.filter((model) => !model.attribute('canDelete'));

  if (!selected.length) return null;

  if (deletable.length && !confirm(extractText(app.translator.trans('core.lib.resource.bulk.delete_confirmation', { count: deletable.length })))) {
    return null;
  }

  running.add(list);
  m.redraw();

  try {
    // The summary reports each failure, so the per-request error alert is suppressed.
    const result = await context.runner.run(deletable, (model) => model.delete({}, { errorHandler: () => {} }));
    const summary: BulkSummary<M> = { ...result, skipped };

    context.showResult(summary);
    await list.gotoAfterRemoval(result.ok.length);

    return summary;
  } finally {
    running.delete(list);
    m.redraw();
  }
}
