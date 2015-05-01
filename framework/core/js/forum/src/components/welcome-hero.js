import Component from 'flarum/component';

export default class WelcomeHero extends Component {
  constructor(props) {
    super(props);

    this.hidden = m.prop(localStorage.getItem('welcomeHidden'));
  }

  hide() {
    localStorage.setItem('welcomeHidden', 'true');
    this.hidden(true);
  }

  view() {
    return this.hidden() ? m('') : m('header.hero.welcome-hero', {config: this.element}, [
      m('div.container', [
        m('button.close.btn.btn-icon.btn-link', {onclick: () => this.$().slideUp(this.hide.bind(this))}, m('i.fa.fa-times')),
        m('div.container-narrow', [
          m('h2', app.config['welcome_title']),
          m('div.subtitle', m.trust(app.config['welcome_message']))
        ])
      ])
    ])
  }
}
