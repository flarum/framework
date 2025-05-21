import app from 'flarum/forum/app';
import Hero, { IHeroAttrs } from 'flarum/forum/components/Hero';
import Icon from 'flarum/common/components/Icon';
import ItemList from 'flarum/common/utils/ItemList';

import type Mithril from 'mithril';

export interface IMessagesPageHeroAttrs extends IHeroAttrs {}

export default class MessagesPageHero<CustomAttrs extends IMessagesPageHeroAttrs = IMessagesPageHeroAttrs> extends Hero<CustomAttrs> {
  className(): string {
    return 'MessagesPageHero';
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('content', <div className="containerNarrow">{this.contentItems().toArray()}</div>, 80);

    return items;
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'title',
      <h1 className="Hero-title">
        <Icon name="fas fa-envelope" /> {app.translator.trans('flarum-messages.forum.messages_page.hero.title')}
      </h1>,
      100
    );

    items.add('subtitle', <div className="Hero-subtitle">{app.translator.trans('flarum-messages.forum.messages_page.hero.subtitle')}</div>, 90);

    return items;
  }
}
