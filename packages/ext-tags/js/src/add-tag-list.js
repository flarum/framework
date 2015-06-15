import { extend } from 'flarum/extension-utils';
import IndexPage from 'flarum/components/index-page';
import NavItem from 'flarum/components/nav-item';
import Separator from 'flarum/components/separator';

import TagNavItem from 'flarum-tags/components/tag-nav-item';
import TagsPage from 'flarum-tags/components/tags-page';

export default function() {
  // Add a link to the tags page, as well as a list of all the tags,
  // to the index page's sidebar.
  extend(IndexPage.prototype, 'navItems', function(items) {
    items.add('tags', NavItem.component({
      icon: 'th-large',
      label: 'Tags',
      href: app.route('tags'),
      config: m.route
    }), {last: true});

    if (app.current instanceof TagsPage) return;

    items.add('separator', Separator.component(), {last: true});

    var params = this.stickyParams();
    var tags = app.store.all('tags');

    var addTag = tag => {
      var currentTag = this.currentTag();
      var active = currentTag === tag;
      if (!active && currentTag) {
        currentTag = currentTag.parent();
        active = currentTag === tag;
      }
      items.add('tag'+tag.id(), TagNavItem.component({tag, params, active}), {last: true});
    }

    tags.filter(tag => tag.position() !== null && !tag.isChild()).sort((a, b) => a.position() - b.position()).forEach(addTag);

    var more = tags.filter(tag => tag.position() === null).sort((a, b) => b.discussionsCount() - a.discussionsCount());

    more.splice(0, 3).forEach(addTag);

    if (more.length) {
      items.add('moreTags', NavItem.component({
        label: 'More...',
        href: app.route('tags'),
        config: m.route
      }), {last: true});;
    }
  });
};
