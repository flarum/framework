import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
import type Dialog from '../../common/models/Dialog';
export interface IDialogListItemAttrs extends ComponentAttrs {
    dialog: Dialog;
    active?: boolean;
    actions?: boolean;
}
export default class DialogListItem<CustomAttrs extends IDialogListItemAttrs = IDialogListItemAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    /**
     * What stands for the others: the groups a group conversation is addressed
     * to, then its other members, as the header shows them. The tiles are
     * pictures only, as a member's avatar is here, so a group's badge carries
     * no tooltip; the conversation is named beside them.
     */
    tiles(): Mithril.Children[];
    /** A group with two or more to show; one left on their own is shown as a direct conversation's. */
    stacksAvatars(): boolean;
    /** The other member's avatar; in a group, the first two of its tiles, one over the other. */
    avatar(): Mithril.Children;
    /** The other member; a group goes by its title, which names its members. */
    name(): Mithril.Children;
    /**
     * The last message, said to be the member's own when it is. In a group,
     * where it could be from any of the others, who wrote it. A notice says
     * what happened.
     */
    preview(): Mithril.Children;
    actionItems(): ItemList<Mithril.Children>;
}
