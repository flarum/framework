import type { Children, Vnode } from 'mithril';
import Component, { ComponentAttrs } from '../../common/Component';

export interface IDashboardWidgetAttrs extends ComponentAttrs {}

export default class DashboardWidget<CustomAttrs extends IDashboardWidgetAttrs = IDashboardWidgetAttrs> extends Component<CustomAttrs> {
  view(vnode: Vnode<CustomAttrs, this>): Children {
    return <div className={'DashboardWidget Widget ' + this.className()}>{this.content()}</div>;
  }

  className() {
    return '';
  }

  content(): Children {
    return null;
  }
}
