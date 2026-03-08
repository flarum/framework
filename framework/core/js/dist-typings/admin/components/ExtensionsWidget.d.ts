export default class ExtensionsWidget extends DashboardWidget<import("./DashboardWidget").IDashboardWidgetAttrs> {
    constructor();
    content(): JSX.Element[];
    renderSection(type: any, items: any, renderItem: any): JSX.Element;
    renderDisabledSection(extensions: any): JSX.Element;
    renderAbandonedItem(item: any): JSX.Element;
    renderSuggestedItem(item: any): JSX.Element;
    renderDisabledItem(extension: any): JSX.Element;
    abandonedExtensions(): {
        extension: import("../AdminApplication").Extension;
    }[];
    suggestedExtensions(): {
        packageId: string;
        description: string;
        suggestedBy: string;
    }[];
    disabledExtensions(): import("../AdminApplication").Extension[];
}
import DashboardWidget from "./DashboardWidget";
