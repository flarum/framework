import Hero, { IHeroAttrs } from './Hero';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

import type Discussion from '../../common/models/Discussion';
import type Mithril from 'mithril';

export interface IDiscussionHeroAttrs extends IHeroAttrs {
  discussion: Discussion;
}

export default class DiscussionHero<CustomAttrs extends IDiscussionHeroAttrs = IDiscussionHeroAttrs> extends Hero<CustomAttrs> {
  className(): string {
    return 'DiscussionHero';
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('items', <ul className="DiscussionHero-items">{listItems(this.items().toArray())}</ul>, 100);

    return items;
  }

  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const discussion = this.attrs.discussion;
    const badges = discussion.badges().toArray();

    if (badges.length) {
      items.add('badges', <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>, 10);
    }

    items.add('title', <h1 className="DiscussionHero-title">{discussion.title()}</h1>);

    return items;
  }
}
