import DashboardWidget from './DashboardWidget';
import type { IDashboardWidgetAttrs } from './DashboardWidget';
import type Mithril from 'mithril';
interface BounceStats {
    hour: number;
    week: number;
    month: number;
    total: number;
    affected: number;
    recovered: number;
    configured: boolean;
}
export default class EmailBounceWidget extends DashboardWidget {
    stats: BounceStats | null;
    loading: boolean;
    loadError: boolean;
    oninit(vnode: Mithril.Vnode<IDashboardWidgetAttrs, this>): void;
    className(): string;
    load(): Promise<void>;
    content(): JSX.Element;
    body(): Mithril.Children;
}
export {};
