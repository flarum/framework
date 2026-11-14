import Component, { ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
export interface IUserCardAttrs extends ComponentAttrs {
    userd: User;
    className?: string;
    editable?: boolean;
    controlsButtonClassName?: string;
}
/**
 * The `UserCard` component displays a user's profile card. This is used both on
 * the `UserPage` (in the hero) and in discussions, shown when hovering over a
 * post author.
 */
export default class UserCard<CustomAttrs extends IUserCardAttrs = IUserCardAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    profileItems(): ItemList<Mithril.Children>;
    avatar(): JSX.Element;
    content(): JSX.Element;
    contentItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list of tidbits of info to show on this user's profile.
     */
    infoItems(): ItemList<Mithril.Children>;
    controlsItems(): ItemList<Mithril.Children>;
}
