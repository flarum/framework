import Hero, { IHeroAttrs } from 'flarum/forum/components/Hero';
import ItemList from 'flarum/common/utils/ItemList';
import type Tag from '../../common/models/Tag';
import type Mithril from 'mithril';
export interface ITagHeroAttrs extends IHeroAttrs {
    model: Tag;
}
export default class TagHero<CustomAttrs extends ITagHeroAttrs = ITagHeroAttrs> extends Hero<CustomAttrs> {
    className(): string;
    style(): Record<string, string> | undefined;
    bodyItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
}
