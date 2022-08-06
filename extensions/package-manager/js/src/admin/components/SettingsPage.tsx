import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import ExtensionPage, { ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';

import QueueSection from './QueueSection';
import ControlSection from './ControlSection';

export default class SettingsPage extends ExtensionPage {
  sections(vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): ItemList<unknown> {
    // @todo add core feature to register sections
    const items = super.sections(vnode);

    if (app.data.settings['flarum-package-manager.queue_jobs']) {
      items.add('queue', <QueueSection />, 5);
    }

    items.add('control', <ControlSection />, 8);

    items.setPriority('content', 10);
    items.setPriority('permissions', 0);

    return items;
  }
}
