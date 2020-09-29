import { extend } from 'flarum/extend';
import DiscussionListState from 'flarum/states/DiscussionListState';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import { truncate } from 'flarum/utils/string';

export default function addStickyControl() {
  extend(DiscussionListState.prototype, 'requestParams', function(params) {
    params.include.push('firstPost');
  });

  extend(DiscussionListItem.prototype, 'infoItems', function(items) {
    const discussion = this.attrs.discussion;

    if (discussion.isSticky() && !this.attrs.params.q && !discussion.lastReadPostNumber()) {
      const firstPost = discussion.firstPost();

      if (firstPost) {
        const excerpt = truncate(firstPost.contentPlain(), 175);

        items.add('excerpt', m.trust(excerpt), -100);
      }
    }
  });
}
