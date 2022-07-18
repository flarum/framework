export default class ExtensionsWidget extends DashboardWidget<import("./DashboardWidget").IDashboardWidgetAttrs> {
    constructor();
    oninit(vnode: any): void;
    categorizedExtensions: {} | undefined;
    content(): JSX.Element;
    extensionCategory(category: any): JSX.Element;
    extensionWidget(extension: any): JSX.Element;
}
import DashboardWidget from "./DashboardWidget";
