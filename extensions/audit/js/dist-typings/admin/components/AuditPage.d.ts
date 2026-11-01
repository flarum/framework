import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export default class AuditPage extends ExtensionPage {
    className(): string;
    /**
     * The log first, then core's Permissions tab. The limited-access settings
     * stay beside the permission they belong to.
     */
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    /**
     * The log browser, shown on the Log tab.
     */
    content(): JSX.Element;
}
