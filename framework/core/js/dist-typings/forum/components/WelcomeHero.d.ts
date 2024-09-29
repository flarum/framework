import Component from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface IWelcomeHeroAttrs {
}
/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero extends Component<IWelcomeHeroAttrs> {
    /**
     * @deprecated Extend the `isHidden` method instead.
     */
    hidden: boolean;
    oninit(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>): void;
    view(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>): JSX.Element | null;
    /**
     * Hide the welcome hero.
     */
    hide(): void;
    /**
     * Determines whether the welcome hero should be hidden.
     *
     * @returns if the welcome hero is hidden.
     */
    isHidden(): boolean;
    viewItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
}
