import type { IInternalModalAttrs } from 'flarum/common/components/Modal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import ErasureRequest from 'src/common/models/ErasureRequest';
import FormModal from 'flarum/common/components/FormModal';
export interface IProcessErasureRequestModalAttrs extends IInternalModalAttrs {
    request: ErasureRequest;
}
export default class ProcessErasureRequestModal<CustomAttrs extends IProcessErasureRequestModalAttrs = IProcessErasureRequestModalAttrs> extends FormModal<CustomAttrs> {
    comments: Stream<string>;
    loadingAnonymization: boolean;
    loadingDeletion: boolean;
    request: ErasureRequest;
    oninit(vnode: Mithril.Vnode<IProcessErasureRequestModalAttrs>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    fields(): ItemList<Mithril.Children>;
    process(mode: string): void;
}
