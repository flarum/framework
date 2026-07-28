import DashboardWidget, { type IDashboardWidgetAttrs } from './DashboardWidget';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface QueueTotals {
    pending: number;
    reserved: number;
    failed: number;
}
export interface QueueStats {
    totals: QueueTotals;
    queues: Record<string, {
        pending: number;
        reserved: number;
    }>;
}
/**
 * A generic queue overview for non-sync drivers. The counts come from the
 * `queue.stats` endpoint, which is backed by a driver-specific provider —
 * so this same widget serves the database driver, and any extension (fof/redis,
 * fof/horizon) that binds its own provider. Extensions can override `tiles()`
 * or `content()` to enrich the display.
 */
export default class QueueWidget<CustomAttrs extends IDashboardWidgetAttrs = IDashboardWidgetAttrs> extends DashboardWidget<CustomAttrs> {
    loading: boolean;
    stats: QueueStats | null;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    className(): string;
    load(): Promise<void>;
    content(): JSX.Element;
    /**
     * Content appended after the widget title — e.g. status pills. Empty by
     * default; a subclass adds items to surface at-a-glance state.
     */
    titleItems(): ItemList<Mithril.Children>;
    /**
     * Action controls on the right of the header. Ships with the refresh button;
     * a subclass can add its own (e.g. a link to a fuller dashboard).
     */
    headerActions(): ItemList<Mithril.Children>;
    tiles(): ItemList<Mithril.Children>;
    /**
     * Render a single tile.
     *
     * @param label   Either a tile key (resolved against this widget's own
     *                translation namespace via `tileLabel()`) or ready-made
     *                label content (a translated string / vnode). Extensions
     *                subclassing this widget can pass their own label directly.
     * @param value   The tile value — a number, string, or vnode.
     */
    tile(label: string | Mithril.Children, value: Mithril.Children, className?: string, onclick?: () => void): Mithril.Children;
    /**
     * Resolve a tile key to its label. Override in a subclass to source tile
     * labels from an extension's own translation namespace.
     */
    tileLabel(key: string): Mithril.Children;
}
