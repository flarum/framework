import Hero, { IHeroAttrs } from 'flarum/forum/components/Hero';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import tagIcon from '../../common/helpers/tagIcon';
import classList from 'flarum/common/utils/classList';
import ItemList from 'flarum/common/utils/ItemList';

import type Tag from '../../common/models/Tag';
import type Mithril from 'mithril';

export interface ITagHeroAttrs extends IHeroAttrs {
  model: Tag;
}

export default class TagHero<CustomAttrs extends ITagHeroAttrs = ITagHeroAttrs> extends Hero<CustomAttrs> {
  className(): string {
    const tag = this.attrs.model;
    const color = tag.color();

    return classList('TagHero', {
      'TagHero--colored': color,
      [textContrastClass(color)]: color,
    });
  }

  style(): Record<string, string> | undefined {
    const tag = this.attrs.model;
    const color = tag.color();

    return color ? { '--hero-bg': color } : undefined;
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('content', <div className="containerNarrow">{this.contentItems().toArray()}</div>, 80);

    return items;
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const tag = this.attrs.model;

    items.add(
      'tag-title',
      <h1 className="Hero-title">
        {tag.icon() && tagIcon(tag, {}, { useColor: false })} {tag.name()}
      </h1>,
      100
    );

    items.add('tag-subtitle', <div className="Hero-subtitle">{tag.description()}</div>, 90);

    return items;
  }
}
