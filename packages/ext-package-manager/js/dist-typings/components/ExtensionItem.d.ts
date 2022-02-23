import Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { Extension as BaseExtension } from 'flarum/admin/AdminApplication';
import { UpdatedPackage } from './Updater';
export declare type Extension = BaseExtension & {
    name: string;
};
export interface ExtensionItemAttrs extends ComponentAttrs {
    extension: Extension;
    updates: UpdatedPackage;
    onClickUpdate: CallableFunction;
    whyNotWarning?: boolean;
    isCore?: boolean;
    updatable?: boolean;
    isDanger?: boolean;
}
export default class ExtensionItem<Attrs extends ExtensionItemAttrs = ExtensionItemAttrs> extends Component<Attrs> {
    view(vnode: Mithril.Vnode<Attrs, this>): Mithril.Children;
    private version;
}
