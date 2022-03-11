export default class StatisticsWidget {
    oninit(vnode: any): void;
    entities: string[] | undefined;
    periods: {
        today: {
            start: Date;
            end: any;
            step: number;
        };
        last_7_days: {
            start: number;
            end: Date;
            step: number;
        };
        last_28_days: {
            start: number;
            end: Date;
            step: number;
        };
        last_12_months: {
            start: number;
            end: Date;
            step: number;
        };
    } | undefined;
    selectedEntity: any;
    selectedPeriod: any;
    className(): string;
    content(): JSX.Element;
    drawChart(vnode: any): void;
    chart: any;
    entity: any;
    period: any;
    changeEntity(entity: any): void;
    changePeriod(period: any): void;
    getTotalCount(entity: any): any;
    getPeriodCount(entity: any, period: any): number;
    getLastPeriod(thisPeriod: any): {
        start: number;
        end: any;
    };
}
