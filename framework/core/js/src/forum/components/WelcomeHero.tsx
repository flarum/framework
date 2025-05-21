import app from '../app';
import Hero, { IHeroAttrs } from './Hero';
import Button from '../../common/components/Button';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';

export interface IWelcomeHeroAttrs extends IHeroAttrs {}

const LOCAL_STORAGE_KEY = 'welcomeHidden';

/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero<CustomAttrs extends IWelcomeHeroAttrs = IWelcomeHeroAttrs> extends Hero<CustomAttrs> {
  className(): string {
    return 'WelcomeHero';
  }

  view() {
    if (this.isHidden()) return null;

    return super.view();
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const slideUp = () => {
      this.$().slideUp(this.hide.bind(this));
    };

    items.add(
      'dismiss-button',
      <Button
        icon="fas fa-times"
        onclick={slideUp}
        className="Hero-close Button Button--icon Button--link"
        aria-label={app.translator.trans('core.forum.welcome_hero.hide')}
      />,
      100
    );

    items.add('content', <div className="containerNarrow">{this.contentItems().toArray()}</div>, 80);

    return items;
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('title', <h1 className="Hero-title">{app.forum.attribute('welcomeTitle')}</h1>, 100);
    items.add('subtitle', <div className="Hero-subtitle">{m.trust(app.forum.attribute('welcomeMessage'))}</div>);

    return items;
  }

  /**
   * Hide the welcome hero.
   */
  hide() {
    localStorage.setItem(LOCAL_STORAGE_KEY, 'true');
  }

  /**
   * Determines whether the welcome hero should be hidden.
   *
   * @returns if the welcome hero is hidden.
   */
  isHidden(): boolean {
    if (!app.forum.attribute<string>('welcomeTitle')?.trim()) return true;
    if (localStorage.getItem(LOCAL_STORAGE_KEY)) return true;

    return false;
  }
}
