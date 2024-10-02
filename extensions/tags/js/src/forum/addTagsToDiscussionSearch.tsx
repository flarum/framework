import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionsSearchItem from 'flarum/forum/components/DiscussionsSearchItem';
import DiscussionsSearchSource from 'flarum/forum/components/DiscussionsSearchSource';
import tagsLabel from '../common/helpers/tagsLabel';

export default function addTagsToDiscussionSearch() {
  extend(DiscussionsSearchSource.prototype, 'includes', function (includes) {
    app.forum.attribute<boolean>('showTagsInDiscussionSearchResults') && includes.push('tags');
  });

  extend(DiscussionsSearchItem.prototype, 'viewItems', function (items) {
    app.forum.attribute<boolean>('showTagsInDiscussionSearchResults') &&
      items.add('tags', <div className="DiscussionSearchResult-tags">{tagsLabel(this.discussion.tags())}</div>, 100);
  });
}
