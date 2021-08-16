import { extend } from 'flarum/common/extend';
import classList from 'flarum/common/utils/classList';

import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';

export default function addStickyClass() {
  extend(DiscussionListItem.prototype, 'elementAttrs', function (attrs) {
    if (this.attrs.discussion.isSticky()) {
      attrs.className = classList(attrs.className, 'DiscussionListItem--sticky');
    }
  });
}
