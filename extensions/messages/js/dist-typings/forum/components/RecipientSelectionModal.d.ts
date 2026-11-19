import UserSelectionModal, { type IUserSelectionModalAttrs } from 'flarum/common/components/UserSelectionModal';
import Group from 'flarum/common/models/Group';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
export interface IRecipientSelectionModalAttrs extends IUserSelectionModalAttrs {
    selectedGroups?: Group[];
    onsubmitGroups: (groups: Group[]) => void;
}
export default class RecipientSelectionModal extends UserSelectionModal<IRecipientSelectionModalAttrs> {
    protected selectedGroups: Stream<Group[]>;
    oninit(vnode: Mithril.Vnode<IRecipientSelectionModalAttrs, this>): void;
    content(): Mithril.Children;
    meetsRequirements(): boolean;
    onsubmit(e: SubmitEvent): void;
}
