import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { UpdatedPackage, UpdateState } from './Updater';
interface MajorUpdaterAttrs extends ComponentAttrs {
    coreUpdate: UpdatedPackage;
    updateState: UpdateState;
}
export default class MajorUpdater<T extends MajorUpdaterAttrs = MajorUpdaterAttrs> extends Component<T> {
    isLoading: string | null;
    updateState: UpdateState;
    oninit(vnode: Mithril.Vnode<T, this>): void;
    view(vnode: Mithril.Vnode<T, this>): Mithril.Children;
    update(dryRun: boolean): void;
}
export {};
