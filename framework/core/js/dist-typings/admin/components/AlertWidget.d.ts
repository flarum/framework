import { AlertAttrs } from '../../common/components/Alert';
import DashboardWidget, { type IDashboardWidgetAttrs } from './DashboardWidget';
import type Mithril from 'mithril';
export interface IAlertWidgetAttrs extends IDashboardWidgetAttrs {
    alert: AlertAttrs;
}
export default class AlertWidget<CustomAttrs extends IAlertWidgetAttrs = IAlertWidgetAttrs> extends DashboardWidget<CustomAttrs> {
    className(): string;
    content(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
