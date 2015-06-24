import NavItem from 'flarum/components/nav-item';
import tagIcon from 'flarum-tags/helpers/tag-icon';

export default class TagNavItem extends NavItem {
  view() {
    var tag = this.props.tag;
    var active = this.constructor.active(this.props);
    var description = tag && tag.description();
    var children;

    if (active && tag) {
      children = app.store.all('tags').filter(child => {
        var parent = child.parent();
        return parent && parent.id() == tag.id();
      });
    }

    return m('li'+(active ? '.active' : ''),
      m('a.has-icon', {
        href: this.props.href,
        config: m.route,
        onclick: () => {
          if (app.cache.discussionList) {
            app.cache.discussionList.forceReload = true;
          }
          m.redraw.strategy('none');
        },
        style: (active && tag) ? 'color: '+tag.color() : '',
        title: description || ''
      }, [
        tagIcon(tag, {className: 'icon'}),
        this.props.label
      ]),
      children && children.length ? m('ul.dropdown-menu', children.map(tag => TagNavItem.component({tag, params: this.props.params}))) : ''
    );
  }

  static props(props) {
    var tag = props.tag;
    props.params.tags = tag ? tag.slug() : 'untagged';
    props.href = app.route('tag', props.params);
    props.label = tag ? tag.name() : 'Untagged';
  }
}
