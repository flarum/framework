import { extend } from 'flarum/extend';
import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import { truncate } from 'flarum/utils/string';

export default function addStickyControl() {
  extend(DiscussionList.prototype, 'requestParams', function(params) {
    params.include.push('firstPost');
  });

  extend(DiscussionListItem.prototype, 'infoItems', function(items) {
    const discussion = this.props.discussion;

    if (discussion.isSticky() && !this.props.params.q && !discussion.lastReadPostNumber()) {
      const firstPost = discussion.firstPost();

      if (firstPost) {
        const excerpt = truncate(firstPost.contentPlain(), 175);

        items.add('excerpt', excerpt, -100);
      }
    }
  });
}
