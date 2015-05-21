import Component from 'flarum/component';

export default class CategoryHero extends Component {
  view() {
    var category = this.props.category;

    return m('header.hero.category-hero', {style: 'color: #fff; background-color: '+category.color()}, [
      m('div.container', [
        m('div.container-narrow', [
          m('h2', category.title()),
          m('div.subtitle', category.description())
        ])
      ])
    ]);
  }
}
