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
            {this.titleItems().toArray()}
          </h3>
          <div className="QueueWidget-headerActions">{this.headerActions().toArray()}</div>
        </div>

        {!this.stats ? <LoadingIndicator /> : <div className="QueueWidget-tiles">{this.tiles().toArray()}</div>}
      </div>
    );
  }

  /**
   * Content appended after the widget title — e.g. status pills. Empty by
   * default; a subclass adds items to surface at-a-glance state.
   */
  titleItems(): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  /**
   * Action controls on the right of the header. Ships with the refresh button;
   * a subclass can add its own (e.g. a link to a fuller dashboard).
   */
  headerActions(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        className="Button Button--icon Button--flat"
        icon="fas fa-sync-alt"
        loading={this.loading}
        onclick={() => this.load()}
        aria-label={app.translator.trans('core.admin.queue_widget.refresh')}
      />,
      100
    );

    return items;
  }

  tiles(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const totals = this.stats!.totals;

    items.add('pending', this.tile('pending', totals.pending), 100);
    items.add('reserved', this.tile('reserved', totals.reserved), 90);
    items.add(
      'failed',
      // When there are failures, the tile becomes a button opening the failed
      // jobs view (exception + retry/delete) — a count with no drill-through
      // is a dead end.
      this.tile(
        'failed',
        totals.failed,
        totals.failed > 0 ? 'QueueWidget-tile--alert' : '',
        totals.failed > 0 ? () => app.modal.show(() => import('./FailedJobsModal')) : undefined
      ),
      80
    );

    return items;
  }

  /**
   * Render a single tile.
   *
   * @param label   Either a tile key (resolved against this widget's own
   *                translation namespace via `tileLabel()`) or ready-made
   *                label content (a translated string / vnode). Extensions
   *                subclassing this widget can pass their own label directly.
   * @param value   The tile value — a number, string, or vnode.
   */
  tile(label: string | Mithril.Children, value: Mithril.Children, className = '', onclick?: () => void): Mithril.Children {
    const inner = [
      <div className="QueueWidget-tileLabel">{typeof label === 'string' ? this.tileLabel(label) : label}</div>,
      <div className="QueueWidget-tileValue">{value}</div>,
    ];

    return onclick ? (
      <button type="button" className={'QueueWidget-tile QueueWidget-tile--action ' + className} onclick={onclick}>
        {inner}
      </button>
    ) : (
      <div className={'QueueWidget-tile ' + className}>{inner}</div>
    );
  }

  /**
   * Resolve a tile key to its label. Override in a subclass to source tile
   * labels from an extension's own translation namespace.
   */
  tileLabel(key: string): Mithril.Children {
    return app.translator.trans(`core.admin.queue_widget.${key}`);
  }
}
