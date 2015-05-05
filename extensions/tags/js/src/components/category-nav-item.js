import NavItem from 'flarum/components/nav-item';
import categoryIcon from 'categories/helpers/category-icon';

export default class CategoryNavItem extends NavItem {
  view() {
    var category = this.props.category;
    var active = this.constructor.active(this.props);
    return m('li'+(active ? '.active' : ''), m('a', {href: this.props.href, config: m.route, onclick: () => {app.cache.discussionList = null; m.redraw.strategy('none')}, style: active ? 'color: '+category.color() : ''}, [
      categoryIcon(category, {className: 'icon'}),
      category.title()
    ]));
  }

  static props(props) {
    var category = props.category;
    props.params.categories = category.slug();
    props.href = app.route('category', props.params);
    props.label = category.title();

    return props;
  }
}
