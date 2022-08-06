import DashboardWidget, { IDashboardWidgetAttrs } from 'flarum/admin/components/DashboardWidget';
import type Mithril from 'mithril';
interface IPeriodDeclaration {
    start: number;
    end: number;
    step: number;
}
export default class StatisticsWidget extends DashboardWidget {
    entities: string[];
    periods: undefined | Record<string, IPeriodDeclaration>;
    chart: any;
    timedData: any;
    lifetimeData: any;
    loadingLifetime: boolean;
    loadingTimed: boolean;
    selectedEntity: string;
    selectedPeriod: undefined | string;
    chartEntity?: string;
    chartPeriod?: string;
    oncreate(vnode: Mithril.VnodeDOM<IDashboardWidgetAttrs, this>): void;
    loadLifetimeData(): Promise<void>;
    loadTimedData(): Promise<void>;
    className(): string;
    content(): JSX.Element;
    drawChart(vnode: Mithril.VnodeDOM<any, any>): void;
    changeEntity(entity: string): void;
    changePeriod(period: string): void;
    getTotalCount(entity: string): number;
    getPeriodCount(entity: string, period: {
        start: number;
        end: number;
    }): number;
    getLastPeriod(thisPeriod: {
        start: number;
        end: number;
    }): {
        start: number;
        end: number;
    };
}
export {};
