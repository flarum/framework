import DashboardWidget, { IDashboardWidgetAttrs } from 'flarum/admin/components/DashboardWidget';
import type Mithril from 'mithril';
export default class MiniStatisticsWidget extends DashboardWidget {
    entities: string[];
    lifetimeData: any;
    loadingLifetime: boolean;
    oncreate(vnode: Mithril.VnodeDOM<IDashboardWidgetAttrs, this>): void;
    loadLifetimeData(): Promise<void>;
    className(): string;
    content(): JSX.Element;
    getTotalCount(entity: string): number;
}
