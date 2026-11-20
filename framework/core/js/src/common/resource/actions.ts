import type Mithril from 'mithril';
import app from '../app';
import type Model from '../Model';
import type ItemList from '../utils/ItemList';
import type { BulkActionContext, BulkProgress, BulkSummary } from './BulkRunner';
import type ResourceListState from './ResourceListState';
import type { Field } from './types';

/**
 * One operation a table offers on its rows, on its selection, or both. `ResourceDefinition`'s
 * `resourceActions()` lists them; `ResourceTable` renders a row button for each row-scoped action
 * that `applies()` to the record and a toolbar button for each bulk-scoped one.
 *
 * An action either runs per record (`run`, through the table's `BulkRunner`, behind
 * `ResourceActionModal` when it is `destructive` or has a `confirm` or `fields`, then
 * `BulkResultModal`), or opens something for one record (`open`, e.g. the edit form). A row uses
 * `open` when given, the selection always uses `run`, so a `bulk`/`both` action needs `run`.
 */
export interface ResourceAction<M extends Model = Model> {
  key: string;
  /** A function gets the number of records it would run for: 1 on a row, the selection's size on the toolbar. */
  label: Mithril.Children | ((count: number) => Mithril.Children);
  icon?: string;
  /** Added to the row and toolbar buttons. */
  className?: string;
  /** Destructive styling + confirmation required. */
  destructive?: boolean;
  /** Confirmation text; a function gets the count of applicable records. Shown in a modal (no native confirm()). */
  confirm?: Mithril.Children | ((count: number) => Mithril.Children);
  /** Per-record applicability; records failing it are `skipped`. Default: all. */
  applies?(model: M): boolean;
  /** Extra inputs collected in the confirmation modal (same Field/InputSpec machinery as forms). Values passed to run(). */
  fields?(): ItemList<Field<M>>;
  /** One operation per record, run through the BulkRunner. Required unless the action is row-only and has `open`. */
  run?(model: M, values: Record<string, unknown>): Promise<unknown>;
  /**
   * What clicking the action on a row does instead of confirm + `run`, e.g. open a form. Not used
   * for the selection.
   */
  open?(model: M, context: ResourceActionContext<M>): void;
  /** Whether to offer it on rows (`row`), on the selection (`bulk`), or both. Default both. */
  scope?: 'row' | 'bulk' | 'both';
}

/**
 * What an action's `open()` gets from the table: the bulk context plus the table's own edit and
 * delete flows (`ResourceTable.edit()` / `.delete()`), which subclasses replace.
 */
export interface ResourceActionContext<M extends Model = Model> extends BulkActionContext<M> {
  edit(model: M): void;
  delete(model: M): void;
}

export function isRowAction<M extends Model>(action: ResourceAction<M>): boolean {
  return action.scope !== 'bulk' && !!(action.open || action.run);
}

export function isBulkAction<M extends Model>(action: ResourceAction<M>): boolean {
  return action.scope !== 'row' && !!action.run;
}

export function actionLabel<M extends Model>(action: ResourceAction<M>, count: number): Mithril.Children {
  return typeof action.label === 'function' ? action.label(count) : action.label;
}

/**
 * The confirmation text for `count` records: the action's `confirm`, else a generic question.
 */
export function actionConfirmation<M extends Model>(action: ResourceAction<M>, count: number): Mithril.Children {
  if (action.confirm != null) return typeof action.confirm === 'function' ? action.confirm(count) : action.confirm;

  return app.translator.trans('core.lib.resource.bulk.confirmation', { count, label: actionLabel(action, count) });
}

/** Whether the action asks before it runs: destructive, with a `confirm`, or with `fields`. */
export function needsConfirmation<M extends Model>(action: ResourceAction<M>): boolean {
  return !!action.destructive || action.confirm != null || !!action.fields;
}

/** Splits `models` into the ones the action `applies()` to and the ones it skips. */
export function partitionByAction<M extends Model>(action: ResourceAction<M>, models: M[]): { applicable: M[]; skipped: M[] } {
  const applicable: M[] = [];
  const skipped: M[] = [];

  models.forEach((model) => (!action.applies || action.applies(model) ? applicable : skipped).push(model));

  return { applicable, skipped };
}

interface ActionRun {
  ids: Set<string>;
  promise: Promise<BulkSummary<any>>;
}

/** The runs in flight per list and action key. */
const running = new WeakMap<ResourceListState<any>, Map<string, ActionRun[]>>();

function runsOf(list: ResourceListState<any>, key: string): ActionRun[] {
  return running.get(list)?.get(key) ?? [];
}

/**
 * Whether an action is running on the list: any action without `key`, else that action, for
 * `model` when given.
 */
export function isActionRunning(list: ResourceListState<any>, key?: string, model?: Model): boolean {
  const keys = running.get(list);

  if (!keys) return false;
  if (key === undefined) return [...keys.values()].some((runs) => runs.length > 0);

  const id = model?.id();

  return runsOf(list, key).some((run) => id === undefined || run.ids.has(id));
}

/**
 * Runs the action over the applicable records through `context.runner`, shows the summary through
 * `context.showResult()` and refreshes the list: `gotoAfterRemoval()` with the number of records
 * that no longer exist (0 reloads the page). A failed reload has already shown its error. While
 * the action runs for any of the records, another call resolves to that run's summary instead.
 */
export function executeAction<M extends Model>(
  action: ResourceAction<M>,
  applicable: M[],
  skipped: M[],
  values: Record<string, unknown>,
  context: BulkActionContext<M>,
  onProgress?: BulkProgress
): Promise<BulkSummary<M>> {
  const { list } = context;
  const ids = new Set(applicable.map((model) => model.id()!));
  const busy = runsOf(list, action.key).find((run) => [...ids].some((id) => run.ids.has(id)));

  if (busy) return busy.promise as Promise<BulkSummary<M>>;

  const keys = running.get(list) ?? new Map<string, ActionRun[]>();
  const runs = keys.get(action.key) ?? [];
  const run: ActionRun = { ids, promise: Promise.resolve() as Promise<any> };

  running.set(list, keys.set(action.key, runs));
  runs.push(run);
  m.redraw();

  run.promise = (async () => {
    try {
      const result = applicable.length
        ? await context.runner.run(applicable, (model) => action.run!(model, values), onProgress)
        : { ok: [], failed: [] };
      const summary: BulkSummary<M> = { ...result, skipped };

      context.showResult(summary, action);
      await list.gotoAfterRemoval(result.ok.filter((model) => !model.exists).length).catch(() => {});

      return summary;
    } finally {
      runs.splice(runs.indexOf(run), 1);
      m.redraw();
    }
  })();

  return run.promise as Promise<BulkSummary<M>>;
}

/**
 * The built-in row action that opens the record's edit form (`context.edit()`), for records whose
 * `attribute` is truthy.
 */
export function editAction<M extends Model>(attribute: string): ResourceAction<M> {
  return {
    key: 'edit',
    label: app.translator.trans('core.lib.resource.bulk.edit_label'),
    icon: 'fas fa-pencil-alt',
    scope: 'row',
    applies: (model) => !!model.attribute(attribute),
    open: (model, context) => context.edit(model),
  };
}

/**
 * The built-in delete, for records whose `attribute` is truthy, on rows unless `scope` says
 * otherwise: a row opens the table's delete modal (`context.delete()`), the selection deletes one
 * request per record (so an extension's replaced `Delete` endpoint runs per record), the
 * per-request error alert suppressed since the summary reports each failure.
 */
export function deleteAction<M extends Model>(attribute: string, scope: ResourceAction<M>['scope'] = 'row'): ResourceAction<M> {
  return {
    key: 'delete',
    label: app.translator.trans('core.lib.resource.bulk.delete_label'),
    icon: 'fas fa-trash-alt',
    destructive: true,
    scope,
    confirm: (count) => app.translator.trans('core.lib.resource.bulk.delete_confirmation', { count }),
    applies: (model) => !!model.attribute(attribute),
    run: (model) => model.delete({}, { errorHandler: () => {} }),
    open: (model, context) => context.delete(model),
  };
}
