import app from 'flarum/admin/app';
import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import AuditBrowser from '../../common/components/AuditBrowser';

export default class AuditPage extends ExtensionPage {
  className() {
    return super.className() + ' AuditPage';
  }

  /**
   * The log first, then core's Permissions tab. The limited-access settings
   * stay beside the permission they belong to.
   */
  pageTabItems(): ItemList<TabItem> {
    const items = super.pageTabItems();

    items.add('log', { id: 'log', label: app.translator.trans('flarum-audit.admin.tabs.log'), icon: 'fas fa-book' }, 100);

    return items;
  }

  pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children {
    if (tab === 'log') return this.content();

    return super.pageTabContent(tab, vnode);
  }

  /**
   * The log browser, shown on the Log tab.
   */
  content() {
    return (
      <div className="AuditPageContainer">
        <AuditBrowser />
      </div>
    );
  }
}
