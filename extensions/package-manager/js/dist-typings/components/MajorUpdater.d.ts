import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { UpdatedPackage, UpdateState } from '../states/ControlSectionState';
export interface MajorUpdaterAttrs extends ComponentAttrs {
    coreUpdate: UpdatedPackage;
    updateState: UpdateState;
}
export declare type MajorUpdaterLoadingTypes = 'major-update' | 'major-update-dry-run';
export default class MajorUpdater<T extends MajorUpdaterAttrs = MajorUpdaterAttrs> extends Component<T> {
    updateState: UpdateState;
    oninit(vnode: Mithril.Vnode<T, this>): void;
    view(): Mithril.Children;
    update(dryRun: boolean): void;
}
