import Component from 'flarum/component';

export default class WelcomeHero extends Component {
  constructor(props) {
    super(props);

    this.title = m.prop('Mithril Forum')
    this.description = m.prop('Hello')
    this.hidden = m.prop(localStorage.getItem('welcomeHidden'))
  }

  hide() {
    localStorage.setItem('welcomeHidden', 'true')
    this.hidden(true)
  }

  view() {
    var root = m.prop()
    var self = this;
    return this.hidden() ? m('') : m('header.hero.welcome-hero', {config: root}, [
      m('div.container', [
        m('button.close.btn.btn-icon.btn-link', {onclick: function() {
          $(root()).slideUp(self.hide.bind(self))
        }}, m('i.fa.fa-times')),
        m('div.container-narrow', [
          m('h2', this.title()),
          m('p', this.description())
        ])
      ])
    ])
  }
}
