import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import RequestError from '../../common/utils/RequestError';
import { Extension } from '../AdminApplication';
import { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
export interface ExtensionPageAttrs extends IPageAttrs {
    id: string;
}
export default class ExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> extends AdminPage<Attrs> {
    extension: Extension;
    changingState: boolean;
    infoFields: {
        discuss: string;
        documentation: string;
        support: string;
        website: string;
        donate: string;
        source: string;
    };
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    className(): string;
    view(vnode: Mithril.VnodeDOM<Attrs, this>): JSX.Element | null;
    header(): JSX.Element[];
    sections(vnode: Mithril.VnodeDOM<Attrs, this>): ItemList;
    content(vnode: Mithril.VnodeDOM<Attrs, this>): JSX.Element;
    topItems(): ItemList;
    infoItems(): ItemList;
    toggle(): void;
    isEnabled(): any;
    onerror(e: RequestError): void;
}
