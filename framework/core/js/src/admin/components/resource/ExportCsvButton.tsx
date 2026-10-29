import app from '../../app';
import Component, { type ComponentAttrs } from '../../../common/Component';
import Button from '../../../common/components/Button';
import type Model from '../../../common/Model';
import { exportCsv } from '../../../common/resource/csv';
import type ResourceListState from '../../../common/resource/ResourceListState';
import type Mithril from 'mithril';
import type { ColumnData } from './ResourceTable';

export interface ExportCsvButtonAttrs<M extends Model = Model> extends ComponentAttrs {
  list: ResourceListState<M>;
  /** The columns to export, read when the export starts. */
  columns: () => [string, ColumnData<M>][];
  /** Stop after this many rows, with a warning. Default: `exportCsv()`'s. */
  maxRows?: number;
}

/**
 * Downloads the given columns of every record matching the list's search, filters and sort. While
 * running, it shows the progress and cancels on click; removing it cancels too.
 */
export default class ExportCsvButton<
  M extends Model = Model,
  CustomAttrs extends ExportCsvButtonAttrs<M> = ExportCsvButtonAttrs<M>,
> extends Component<CustomAttrs> {
  /** The running export, if any. */
  exporting: { controller: AbortController; rows: number; total: number | null } | null = null;

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.exporting?.controller.abort();
  }

  view(): Mithril.Children {
    const exporting = this.exporting;

    if (exporting) {
      return (
        <Button
          className="Button ResourceTable-export ResourceTable-export--running"
          icon="fas fa-times"
          onclick={() => exporting.controller.abort()}
        >
          {exporting.total
            ? app.translator.trans('core.admin.resource.table.export_progress_total', { count: exporting.rows, total: exporting.total })
            : app.translator.trans('core.admin.resource.table.export_progress', { count: exporting.rows })}
        </Button>
      );
    }

    return (
      <Button className="Button ResourceTable-export" icon="fas fa-file-csv" onclick={() => this.export()}>
        {app.translator.trans('core.admin.resource.table.export_button')}
      </Button>
    );
  }

  /** Run the export; a no-op while one runs. Settles once it ends, failed or cancelled. */
  export(): Promise<void> {
    if (this.exporting) return Promise.resolve();

    const controller = new AbortController();
    this.exporting = { controller, rows: 0, total: null };

    return exportCsv(this.attrs.list, {
      columns: this.attrs.columns(),
      maxRows: this.attrs.maxRows,
      signal: controller.signal,
      onProgress: (rows, total) => {
        if (this.exporting) Object.assign(this.exporting, { rows, total });
        m.redraw();
      },
    })
      .then(({ rows, truncated }) => {
        if (truncated) app.alerts.show({ type: 'warning' }, app.translator.trans('core.admin.resource.table.export_truncated', { count: rows }));
      })
      .catch((error) => {
        if (error?.name === 'AbortError') return;

        console.error(`ExportCsvButton(${this.attrs.list.definition.type}): export failed`, error);
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.resource.table.export_error'));
      })
      .finally(() => {
        this.exporting = null;
        m.redraw();
      });
  }
}
