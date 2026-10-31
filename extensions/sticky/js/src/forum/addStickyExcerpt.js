import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import { truncate } from 'flarum/common/utils/string';

export default function addStickyExcerpt() {
  extend(DiscussionListItem.prototype, 'infoItems', function (items) {
    const discussion = this.attrs.discussion;

    if (app.forum.attribute('excerptDisplayEnabled') && discussion.isSticky() && !this.attrs.params.q && !discussion.lastReadPostNumber()) {
      // Serialized as a plain-text attribute on sticky discussions — the
      // first post itself is no longer included in list payloads.
      const excerpt = discussion.attribute('firstPostExcerpt');

      if (excerpt) {
        // Wrapping in <div> because ItemList entries need to be vnodes
        items.add('excerpt', <div>{truncate(excerpt, 175)}</div>, -100);
      }
    }
  });
}
