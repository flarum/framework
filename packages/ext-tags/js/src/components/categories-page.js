import Component from 'flarum/component';
import WelcomeHero from 'flarum/components/welcome-hero';
import icon from 'flarum/helpers/icon';

export default class CategoriesPage extends Component {
  constructor(props) {
    super(props);

    this.categories = m.prop(app.store.all('categories'));
  }

  view() {
    return m('div.categories-area', [
      m('div.title-control.categories-forum-title', app.config.forum_title),
      WelcomeHero.component(),
      m('div.container', [
        m('ul.category-list.category-list-tiles', [
          m('li.filter-tile', [
            m('a', {href: app.route('index'), config: m.route}, 'All Discussions'),
            // m('ul.filter-list', [
              // m('li', m('a', {href: app.route('index'), config: m.route}, m('span', [icon('star'), ' Following']))),
              // m('li', m('a', {href: app.route('index'), config: m.route}, m('span', [icon('envelope-o'), ' Inbox'])))
            // ])
          ]),
          this.categories().map(category =>
            m('li.category-tile', {style: 'background-color: '+category.color()}, [
              m('a', {href: app.route('category', {categories: category.slug()}), config: m.route}, [
                m('h3.title', category.title()),
                m('p.description', category.description()),
                m('span.count', category.discussionsCount()+' discussions'),
              ])
            ])
          )
        ])
      ])
    ]);
  }
}
