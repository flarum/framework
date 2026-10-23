import Component, { type ComponentAttrs } from 'flarum/common/Component';
import { type Extension as ExtensionInfo } from 'flarum/admin/AdminApplication';
import ExternalExtension from '../models/ExternalExtension';
import { UpdatedPackage } from '../states/ControlSectionState';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export type CommonExtension = ExternalExtension | ExtensionInfo;
export interface IExtensionAttrs extends ComponentAttrs {
    extension: CommonExtension;
    updates?: UpdatedPackage;
    onClickUpdate?: CallableFunction | {
        soft: CallableFunction;
        hard: CallableFunction;
    };
    whyNotWarning?: boolean;
    isCore?: boolean;
    updatable?: boolean;
    isDanger?: boolean;
}
export default class ExtensionCard<CustomAttrs extends IExtensionAttrs = IExtensionAttrs> extends Component<CustomAttrs> {
    getExtension(): ExtensionInfo;
    view(): JSX.Element;
    icon(): JSX.Element;
    badges(): ItemList<Mithril.Children>;
    metaItems(): ItemList<Mithril.Children>;
    actionItems(): ItemList<Mithril.Children>;
    version(v: string): string;
}
