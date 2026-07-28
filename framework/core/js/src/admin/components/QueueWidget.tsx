import app from '../app';
import DashboardWidget, { type IDashboardWidgetAttrs } from './DashboardWidget';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Icon from '../../common/components/Icon';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export interface QueueTotals {
  pending: number;
  reserved: number;
  failed: number;
}

export interface QueueStats {
  totals: QueueTotals;
  queues: Record<string, { pending: number; reserved: number }>;
}

/**
 * A generic queue overview for non-sync drivers. The counts come from the
 * `queue.stats` endpoint, which is backed by a driver-specific provider —
 * so this same widget serves the database driver, and any extension (fof/redis,
 * fof/horizon) that binds its own provider. Extensions can override `tiles()`
 * or `content()` to enrich the display.
 */
export default class QueueWidget<CustomAttrs extends IDashboardWidgetAttrs = IDashboardWidgetAttrs> extends DashboardWidget<CustomAttrs> {
  loading = true;
  stats: QueueStats | null = null;

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);
    this.load();
  }

  className() {
    return 'QueueWidget';
  }

  load() {
    this.loading = true;

    return app
      .request<QueueStats>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/queue/stats',
      })
      .then((stats) => {
        this.stats = stats;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  content() {
    return (
      <div className="QueueWidget-content">
        <div className="QueueWidget-header">
          <h3 className="QueueWidget-title">
            <Icon name="fas fa-stream" /> {app.translator.trans('core.admin.queue_widget.title')}
          </h3>
          <Button
            className="Button Button--icon Button--flat"
            icon="fas fa-sync-alt"
            loading={this.loading}
            onclick={() => this.load()}
            aria-label={app.translator.trans('core.admin.queue_widget.refresh')}
          />
        </div>

        {!this.stats ? <LoadingIndicator /> : <div className="QueueWidget-tiles">{this.tiles().toArray()}</div>}
      </div>
    );
  }

  tiles(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const totals = this.stats!.totals;

    items.add('pending', this.tile('pending', totals.pending), 100);
    items.add('reserved', this.tile('reserved', totals.reserved), 90);
    items.add('failed', this.tile('failed', totals.failed, totals.failed > 0 ? 'QueueWidget-tile--alert' : ''), 80);

    return items;
  }

  tile(key: string, value: number, className = ''): Mithril.Children {
    return (
      <div className={'QueueWidget-tile ' + className}>
        <div className="QueueWidget-tileValue">{value}</div>
        <div className="QueueWidget-tileLabel">{app.translator.trans(`core.admin.queue_widget.${key}`)}</div>
      </div>
    );
  }
}
