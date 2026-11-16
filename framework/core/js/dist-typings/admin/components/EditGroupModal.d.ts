import FormModal, { IFormModalAttrs } from '../../common/components/FormModal';
import Group from '../../common/models/Group';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import IconImageState from '../../common/states/IconImageState';
export interface IEditGroupModalAttrs extends IFormModalAttrs {
    group?: Group;
}
/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal<CustomAttrs extends IEditGroupModalAttrs = IEditGroupModalAttrs> extends FormModal<CustomAttrs> {
    group: Group;
    nameSingular: Stream<string>;
    namePlural: Stream<string>;
    icon: Stream<string>;
    color: Stream<string>;
    isHidden: Stream<boolean>;
    iconImage: IconImageState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    className(): string;
    title(): JSX.Element;
    content(): JSX.Element;
    fields(): ItemList<Mithril.Children>;
    submitData(): {
        nameSingular: string;
        namePlural: string;
        color: string;
        icon: string;
        isHidden: boolean;
    };
    onsubmit(e: SubmitEvent): void;
    deleteGroup(): void;
}
