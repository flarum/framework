import app from '../../app';
import type Model from '../../../common/Model';
import { exportCsv, type ExportColumn } from '../../../common/resource/csv';
import type ResourceListState from '../../../common/resource/ResourceListState';

/**
 * One table's CSV export: downloads the given columns of every record matching the list's search,
 * filters and sort, and tracks the running export (`rows`, `total`) for the table to show its
 * progress. `cancel()` aborts it.
 */
export default class CsvExport<M extends Model = Model> {
  /** The running export, if any. */
  running: { controller: AbortController; rows: number; total: number | null } | null = null;

  constructor(
    readonly list: ResourceListState<M>,
    /** Stop after this many rows, with a warning. Default: `exportCsv()`'s. */
    readonly maxRows?: number
  ) {}

  /** Run the export over `columns`; a no-op while one runs. Settles once it ends, failed or cancelled. */
  start(columns: [string, ExportColumn<M>][]): Promise<void> {
    if (this.running) return Promise.resolve();

    const controller = new AbortController();
    this.running = { controller, rows: 0, total: null };
    m.redraw();

    return exportCsv(this.list, {
      columns,
      maxRows: this.maxRows,
      signal: controller.signal,
      onProgress: (rows, total) => {
        if (this.running) Object.assign(this.running, { rows, total });
        m.redraw();
      },
    })
      .then(({ rows, truncated }) => {
        if (truncated) app.alerts.show({ type: 'warning' }, app.translator.trans('core.admin.resource.table.export_truncated', { count: rows }));
      })
      .catch((error) => {
        if (error?.name === 'AbortError') return;

        console.error(`CsvExport(${this.list.definition.type}): export failed`, error);
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.resource.table.export_error'));
      })
      .finally(() => {
        this.running = null;
        m.redraw();
      });
  }

  cancel(): void {
    this.running?.controller.abort();
  }
}
