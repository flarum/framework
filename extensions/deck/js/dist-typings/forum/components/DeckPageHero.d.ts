import Hero, { type IHeroAttrs } from 'flarum/forum/components/Hero';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export interface IDeckPageHeroAttrs extends IHeroAttrs {
    ondismiss?: () => void;
}
/**
 * Introduces the page, like the Messages page's hero, and can be dismissed for
 * good, like core's WelcomeHero.
 */
export default class DeckPageHero<CustomAttrs extends IDeckPageHeroAttrs = IDeckPageHeroAttrs> extends Hero<CustomAttrs> {
    /** Remembered per browser, as core's welcome hero is. */
    static isHidden(): boolean;
    className(): string;
    view(): JSX.Element | null;
    bodyItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
    hide(): void;
}
