import Hero, { IHeroAttrs } from './Hero';
import ItemList from '../../common/utils/ItemList';
import type Discussion from '../../common/models/Discussion';
import type Mithril from 'mithril';
export interface IDiscussionHeroAttrs extends IHeroAttrs {
    discussion: Discussion;
}
export default class DiscussionHero<CustomAttrs extends IDiscussionHeroAttrs = IDiscussionHeroAttrs> extends Hero<CustomAttrs> {
    className(): string;
    bodyItems(): ItemList<Mithril.Children>;
    items(): ItemList<Mithril.Children>;
}
