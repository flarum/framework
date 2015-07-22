import EventPost from 'flarum/components/EventPost';
import punctuate from 'flarum/helpers/punctuate';
import tagsLabel from 'tags/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  icon() {
    return 'tag';
  }

  descriptionKey() {
    return 'tags.discussion_tagged_post';
  }

  descriptionData() {
    const post = this.props.post;
    const oldTags = post.content()[0];
    const newTags = post.content()[1];

    function diffTags(tags1, tags2) {
      return tags1
        .filter(tag => tags2.indexOf(tag) === -1)
        .map(id => app.store.getById('tags', id));
    }

    const added = diffTags(newTags, oldTags);
    const removed = diffTags(oldTags, newTags);
    const actions = [];

    if (added.length) {
      actions.push(app.trans('tags.added_tags', {
        tags: tagsLabel(added, {link: true}),
        count: added
      }));
    }

    if (removed.length) {
      actions.push(app.trans('tags.removed_tags', {
        tags: tagsLabel(removed, {link: true}),
        count: removed
      }));
    }

    return {
      action: punctuate(actions),
      count: added.length + removed.length
    };
  }
}
