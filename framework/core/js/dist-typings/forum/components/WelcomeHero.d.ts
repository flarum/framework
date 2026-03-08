import Hero, { IHeroAttrs } from './Hero';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface IWelcomeHeroAttrs extends IHeroAttrs {
}
/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero<CustomAttrs extends IWelcomeHeroAttrs = IWelcomeHeroAttrs> extends Hero<CustomAttrs> {
    className(): string;
    view(): JSX.Element | null;
    bodyItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
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
}
