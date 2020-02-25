import { extend } from 'flarum/extend';
import IndexPage from 'flarum/components/IndexPage';
import Separator from 'flarum/components/Separator';
import LinkButton from 'flarum/components/LinkButton';

import TagLinkButton from './components/TagLinkButton';
import TagsPage from './components/TagsPage';
import sortTags from '../common/utils/sortTags';

export default function() {
  // Add a link to the tags page, as well as a list of all the tags,
  // to the index page's sidebar.
  extend(IndexPage.prototype, 'navItems', function(items) {
    items.add('tags', LinkButton.component({
      icon: 'fas fa-th-large',
      children: app.translator.trans('flarum-tags.forum.index.tags_link'),
      href: app.route('tags')
    }), -10);

    if (app.current instanceof TagsPage) return;

    items.add('separator', Separator.component(), -12);

    const params = this.stickyParams();
    const tags = app.store.all('tags');
    const currentTag = this.currentTag();

    const addTag = tag => {
      let active = currentTag === tag;

      if (!active && currentTag) {
        active = currentTag.parent() === tag;
      }

      items.add('tag' + tag.id(), TagLinkButton.component({tag, params, active}), -14);
    };

    sortTags(tags)
      .filter(tag => tag.position() !== null && (!tag.isChild() || (currentTag && (tag.parent() === currentTag || tag.parent() === currentTag.parent()))))
      .forEach(addTag);

    const more = tags
      .filter(tag => tag.position() === null)
      .sort((a, b) => b.discussionCount() - a.discussionCount());

    more.splice(0, 3).forEach(addTag);

    if (more.length) {
      items.add('moreTags', LinkButton.component({
        children: app.translator.trans('flarum-tags.forum.index.more_link'),
        href: app.route('tags')
      }), -16);
    }
  });
}
