import Alert, { AlertAttrs } from '../../common/components/Alert';
import DashboardWidget, { type IDashboardWidgetAttrs } from './DashboardWidget';
import classList from '../../common/utils/classList';
import type Mithril from 'mithril';

export interface IAlertWidgetAttrs extends IDashboardWidgetAttrs {
  alert: AlertAttrs;
}

export default class AlertWidget<CustomAttrs extends IAlertWidgetAttrs = IAlertWidgetAttrs> extends DashboardWidget<CustomAttrs> {
  className() {
    return classList('AlertWidget', this.attrs.className);
  }

  content(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return <Alert {...vnode.attrs.alert}>{vnode.children}</Alert>;
  }
}
