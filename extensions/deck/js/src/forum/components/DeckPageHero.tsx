import app from 'flarum/forum/app';
import Hero, { type IHeroAttrs } from 'flarum/forum/components/Hero';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export interface IDeckPageHeroAttrs extends IHeroAttrs {
  ondismiss?: () => void;
}

const LOCAL_STORAGE_KEY = 'flarum-deck.heroHidden';

/**
 * Introduces the page, like the Messages page's hero, and can be dismissed for
 * good, like core's WelcomeHero.
 */
export default class DeckPageHero<CustomAttrs extends IDeckPageHeroAttrs = IDeckPageHeroAttrs> extends Hero<CustomAttrs> {
  /** Remembered per browser, as core's welcome hero is. */
  static isHidden(): boolean {
    try {
      return !!localStorage.getItem(LOCAL_STORAGE_KEY);
    } catch {
      return false;
    }
  }

  className(): string {
    return 'DeckPageHero';
  }

  view() {
    if (DeckPageHero.isHidden()) return null;

    return super.view();
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'dismiss-button',
      <Button
        icon="fas fa-times"
        onclick={() => this.$().slideUp(() => this.hide())}
        className="Hero-close Button Button--icon Button--link"
        aria-label={app.translator.trans('flarum-deck.forum.page.hero.hide_label')}
      />,
      100
    );

    items.add('content', <div className="containerNarrow">{this.contentItems().toArray()}</div>, 80);

    return items;
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'title',
      <h1 className="Hero-title">
        <Icon name="fas fa-table-columns" /> {app.translator.trans('flarum-deck.forum.page.title')}
      </h1>,
      100
    );

    items.add('subtitle', <div className="Hero-subtitle">{app.translator.trans('flarum-deck.forum.page.hero.subtitle')}</div>, 90);

    return items;
  }

  hide(): void {
    try {
      localStorage.setItem(LOCAL_STORAGE_KEY, 'true');
    } catch {
      // Private browsing and the like: it stays hidden until the page reloads.
    }

    this.attrs.ondismiss?.();
  }
}
