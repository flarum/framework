/**
 * The `UserCard` component displays a user's profile card. This is used both on
 * the `UserPage` (in the hero) and in discussions, shown when hovering over a
 * post author.
 *
 * ### Attrs
 *
 * - `user`
 * - `className`
 * - `editable`
 * - `controlsButtonClassName`
 */
export default class UserCard extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Build an item list of tidbits of info to show on this user's profile.
     *
     * @return {ItemList}
     */
    infoItems(): ItemList;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
