import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../../common/Component';

export interface IDashboardWidgetAttrs extends ComponentAttrs {}

export default class DashboardWidget<CustomAttrs extends IDashboardWidgetAttrs = IDashboardWidgetAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return <div className={'DashboardWidget Widget ' + this.className()}>{this.content(vnode)}</div>;
  }

  className() {
    return '';
  }

  content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return null;
  }
}
