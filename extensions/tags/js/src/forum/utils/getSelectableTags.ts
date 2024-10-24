import type Tag from '../../common/models/Tag';
import type Discussion from 'flarum/common/models/Discussion';

export default function getSelectableTags(discussion: Discussion) {
  let tags = app.store.all<Tag>('tags');

  if (discussion) {
    const discussionTags = discussion.tags() || [];
    tags = tags.filter((tag) => tag.canAddToDiscussion() || discussionTags.includes(tag));
  } else {
    tags = tags.filter((tag) => tag.canStartDiscussion());
  }

  return tags;
}
