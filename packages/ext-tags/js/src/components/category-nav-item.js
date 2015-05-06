import NavItem from 'flarum/components/nav-item';
import categoryIcon from 'categories/helpers/category-icon';

export default class CategoryNavItem extends NavItem {
  view() {
    var category = this.props.category;
    var active = this.constructor.active(this.props);
    return m('li'+(active ? '.active' : ''), m('a', {href: this.props.href, config: m.route, onclick: () => {app.cache.discussionList = null; m.redraw.strategy('none')}, style: (active && category) ? 'color: '+category.color() : '', title: category ? category.description() : ''}, [
      categoryIcon(category, {className: 'icon'}),
      this.props.label
    ]));
  }

  static props(props) {
    var category = props.category;
    props.params.categories = category ? category.slug() : 'uncategorized';
    props.href = app.route('category', props.params);
    props.label = category ? category.title() : 'Uncategorized';

    return props;
  }
}
