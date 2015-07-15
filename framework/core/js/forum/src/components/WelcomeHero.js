import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

/**
 * The `WelcomeHero` component displays a hero that welcomes the user to the
 * forum.
 */
export default class WelcomeHero extends Component {
  constructor(...args) {
    super(...args);

    this.hidden = localStorage.getItem('welcomeHidden');
  }

  view() {
    if (this.hidden) return <div/>;

    const slideUp = () => {
      this.$().slideUp(this.hide.bind(this));
    };

    return (
      <header className="hero welcome-hero">
        <div class="container">
          <button className="close btn btn-icon btn-link" onclick={slideUp}>
            {icon('times')}
          </button>

          <div className="container-narrow">
            <h2>{app.forum.attribute('welcomeTitle')}</h2>
            <div className="subtitle">{m.trust(app.forum.attribute('welcomeMessage'))}</div>
          </div>
        </div>
      </header>
    );
  }

  /**
   * Hide the welcome hero.
   */
  hide() {
    localStorage.setItem('welcomeHidden', 'true');

    this.hidden = true;
  }
}
