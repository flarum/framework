import DashboardWidget, { IDashboardWidgetAttrs } from './DashboardWidget';
import { Extension } from '../AdminApplication';
import type Mithril from 'mithril';
export interface IExtensionsWidgetAttrs extends IDashboardWidgetAttrs {
}
export default class ExtensionsWidget<CustomAttrs extends IExtensionsWidgetAttrs = IExtensionsWidgetAttrs> extends DashboardWidget<CustomAttrs> {
    extension: Extension;
    categorizedExtensions: any;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    content(): JSX.Element;
    extensionCategory(category: any): JSX.Element;
    extensionWidget(extension: any): JSX.Element;
}
