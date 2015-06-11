import Component from 'flarum/component';

export default class TagHero extends Component {
  view() {
    var tag = this.props.tag;
    var color = tag.color();

    return m('header.hero.tag-hero', {style: color ? 'color: #fff; background-color: '+tag.color() : ''}, [
      m('div.container', [
        m('div.container-narrow', [
          m('h2', tag.name()),
          m('div.subtitle', tag.description())
        ])
      ])
    ]);
  }
}
