import type Mithril from 'mithril';
import ExtensionPage, { ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';
export default class SettingsPage extends ExtensionPage {
    sections(vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): ItemList<unknown>;
}
