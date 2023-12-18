import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { Extension } from 'flarum/admin/AdminApplication';
import { UpdatedPackage } from '../states/ControlSectionState';
export interface ExtensionItemAttrs extends ComponentAttrs {
    extension: Extension;
    updates: UpdatedPackage;
    onClickUpdate: CallableFunction | {
        soft: CallableFunction;
        hard: CallableFunction;
    };
    whyNotWarning?: boolean;
    isCore?: boolean;
    updatable?: boolean;
    isDanger?: boolean;
}
export default class ExtensionItem<Attrs extends ExtensionItemAttrs = ExtensionItemAttrs> extends Component<Attrs> {
    view(vnode: Mithril.Vnode<Attrs, this>): Mithril.Children;
    version(v: string): string;
}
