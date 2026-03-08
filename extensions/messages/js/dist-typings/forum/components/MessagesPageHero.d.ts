import Hero, { IHeroAttrs } from 'flarum/forum/components/Hero';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export interface IMessagesPageHeroAttrs extends IHeroAttrs {
}
export default class MessagesPageHero<CustomAttrs extends IMessagesPageHeroAttrs = IMessagesPageHeroAttrs> extends Hero<CustomAttrs> {
    className(): string;
    bodyItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
}
