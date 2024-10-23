import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../../common/Component';
export interface IDashboardWidgetAttrs extends ComponentAttrs {
}
export default class DashboardWidget<CustomAttrs extends IDashboardWidgetAttrs = IDashboardWidgetAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    className(): string;
    content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
}
