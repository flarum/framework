import app from '../app';
import Component from '../../common/Component';
import Button from '../../common/components/Button';
import type Mithril from 'mithril';

export interface IWelcomeHeroAttrs {}

const LOCAL_STORAGE_KEY = 'welcomeHidden';

/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero extends Component<IWelcomeHeroAttrs> {
  /**
   * @deprecated Extend the `isHidden` method instead.
   */
  hidden: boolean = false;

  oninit(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>) {
    if (this.isHidden()) return null;

    const slideUp = () => {
      this.$().slideUp(this.hide.bind(this));
    };

    return (
      <header className="Hero WelcomeHero">
        <div className="container">
          <Button
            icon="fas fa-times"
            onclick={slideUp}
            className="Hero-close Button Button--icon Button--link"
            aria-label={app.translator.trans('core.forum.welcome_hero.hide')}
          />

          <div className="containerNarrow">
            <h1 className="Hero-title">{app.forum.attribute('welcomeTitle')}</h1>
            <div className="Hero-subtitle">{m.trust(app.forum.attribute('welcomeMessage'))}</div>
          </div>
        </div>
      </header>
    );
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
    if (this.hidden) return true;

    return false;
  }
}
