import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Mithril from 'mithril';
import Stream from 'flarum/common/utils/Stream';
import { type Repository } from './ConfigureComposer';
export interface IRepositoryModalAttrs extends IInternalModalAttrs {
    onsubmit: (repository: Repository, key: string) => void;
    name?: string;
    repository?: Repository;
}
export default class RepositoryModal<CustomAttrs extends IRepositoryModalAttrs = IRepositoryModalAttrs> extends Modal<CustomAttrs> {
    protected name: Stream<string>;
    protected repository: Stream<Repository>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    submit(): void;
}
