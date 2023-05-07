import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import Separator from 'flarum/common/components/Separator';
import LinkButton from 'flarum/common/components/LinkButton';

import TagLinkButton from './components/TagLinkButton';
import TagsPage from './components/TagsPage';
import app from 'flarum/forum/app';
import sortTags from '../common/utils/sortTags';

export default function () {
  // Add a link to the tags page, as well as a list of all the tags,
  // to the index page's sidebar.
  extend(IndexPage.prototype, 'navItems', function (items) {
    items.add(
      'tags',
      <LinkButton icon="fas fa-th-large" href={app.route('tags')}>
        {app.translator.trans('flarum-tags.forum.index.tags_link')}
      </LinkButton>,
      -10
    );

    if (app.current.matches(TagsPage)) return;

    items.add('separator', <Separator />, -12);

    const params = app.search.stickyParams();
    const tags = app.store.all('tags');
    const currentTag = this.currentTag();

    const addTag = (tag) => {
      let active = currentTag === tag;

      if (!active && currentTag) {
        active = currentTag.parent() === tag;
      }

      // tag.name() is passed here as children even though it isn't used directly
      // because when we need to get the active child in SelectDropdown, we need to
      // use its children to populate the dropdown. The problem here is that `view`
      // on TagLinkButton is only called AFTER SelectDropdown, so no children are available
      // for SelectDropdown to use at the time.
      items.add(
        'tag' + tag.id(),
        <TagLinkButton model={tag} params={params} active={active}>
          {tag?.name()}
        </TagLinkButton>,
        -14
      );
    };

    sortTags(tags)
      .filter(
        (tag) => tag.position() !== null && (!tag.isChild() || (currentTag && (tag.parent() === currentTag || tag.parent() === currentTag.parent())))
      )
      .forEach(addTag);

    const more = tags.filter((tag) => tag.position() === null).sort((a, b) => b.discussionCount() - a.discussionCount());

    more.splice(0, 3).forEach(addTag);

    if (more.length) {
      items.add('moreTags', <LinkButton href={app.route('tags')}>{app.translator.trans('flarum-tags.forum.index.more_link')}</LinkButton>, -16);
    }
  });
}
