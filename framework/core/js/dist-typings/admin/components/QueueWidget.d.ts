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
    tiles(): ItemList<Mithril.Children>;
    tile(key: string, value: number, className?: string, onclick?: () => void): Mithril.Children;
}
