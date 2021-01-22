import { extend } from 'flarum/extend';
import DiscussionListState from 'flarum/states/DiscussionListState';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import DiscussionPage from 'flarum/components/DiscussionPage';
import IndexPage from 'flarum/components/IndexPage';
import { truncate } from 'flarum/utils/string';

export default function addStickyControl() {
  extend(DiscussionListState.prototype, 'requestParams', function(params) {
    if (app.current.matches(IndexPage) || app.current.matches(DiscussionPage)) {
      params.include.push('firstPost');
    }
  });

  extend(DiscussionListItem.prototype, 'infoItems', function(items) {
    const discussion = this.attrs.discussion;

    if (discussion.isSticky() && !this.attrs.params.q && !discussion.lastReadPostNumber()) {
      const firstPost = discussion.firstPost();

      if (firstPost) {
        const excerpt = truncate(firstPost.contentPlain(), 175);

        // Wrapping in <div> because ItemList entries need to be vnodes
        items.add('excerpt', <div>{excerpt}</div>, -100);
      }
    }
  });
}
