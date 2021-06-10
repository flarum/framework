import { extend, override } from 'flarum/extend';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionListState from 'flarum/states/DiscussionListState';
import GlobalSearchState from 'flarum/states/GlobalSearchState';

import TagHero from './components/TagHero';

export default function() {
  IndexPage.prototype.currentTag = function() {
    if (this.currentActiveTag) {
      return this.currentActiveTag;
    }

    const slug = app.search.params().tags;
    let tag = null;

    if (slug) {
      tag = app.store.getBy('tags', 'slug', slug);
    }

    if (slug && !tag || (tag && !tag.isChild() && !tag.children())) {
      // Unlike the backend, no need to fetch parent.children because if we're on
      // a child tag page, then either:
      //    - We loaded in that child tag (and its siblings) in the API document
      //    - We first navigated to the current tag's parent, which would have loaded in the current tag's siblings.
      app.store.find('tags', slug, { include: 'children,children.parent,parent,state'}).then(() => {
        this.currentActiveTag = app.store.getBy('tags', 'slug', slug);

        m.redraw();
      });
    }

    if (tag) {
      this.currentActiveTag = tag;
      return this.currentActiveTag;
    }
  };

  // If currently viewing a tag, insert a tag hero at the top of the view.
  override(IndexPage.prototype, 'hero', function(original) {
    const tag = this.currentTag();

    if (tag) return <TagHero model={tag} />;

    return original();
  });

  extend(IndexPage.prototype, 'view', function(vdom) {
    const tag = this.currentTag();

    if (tag) vdom.attrs.className += ' IndexPage--tag'+tag.id();
  });

  extend(IndexPage.prototype, 'setTitle', function() {
    const tag = this.currentTag();

    if (tag) {
      app.setTitle(tag.name());
    }
  });

  // If currently viewing a tag, restyle the 'new discussion' button to use
  // the tag's color, and disable if the user isn't allowed to edit.
  extend(IndexPage.prototype, 'sidebarItems', function(items) {
    const tag = this.currentTag();

    if (tag) {
      const color = tag.color();
      const canStartDiscussion = tag.canStartDiscussion() || !app.session.user;

      if (color) {
        items.get('newDiscussion').attrs.style = {backgroundColor: color};
      }

      items.get('newDiscussion').attrs.disabled = !canStartDiscussion;
      items.get('newDiscussion').children = app.translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button');
    }
  });

  // Add a parameter for the global search state to pass on to the
  // DiscussionListState that will let us filter discussions by tag.
  extend(GlobalSearchState.prototype, 'params', function(params) {
    params.tags = m.route.param('tags');
  });

  // Translate that parameter into a gambit appended to the search query.
  extend(DiscussionListState.prototype, 'requestParams', function(params) {
    params.include.push('tags', 'tags.parent');

    if (this.params.tags) {
      params.filter.tag = this.params.tags;
      // TODO: replace this with a more robust system.
      const q = params.filter.q;
      if (q) {
        params.filter.q = `${q} tag:${this.params.tags}`;
      }
    }
  });
}
