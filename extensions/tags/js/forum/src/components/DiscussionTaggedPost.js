import EventPost from 'flarum/components/EventPost';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import tagsLabel from 'flarum/tags/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  icon() {
    return 'tag';
  }

  descriptionKey() {
    return 'flarum-tags.forum.discussion_tagged_post';
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
      actions.push(app.translator.transChoice('flarum-tags.forum.added_tags', added, {
        tags: tagsLabel(added, {link: true}),
        count: added
      }));
    }

    if (removed.length) {
      actions.push(app.translator.transChoice('flarum-tags.forum.removed_tags', removed, {
        tags: tagsLabel(removed, {link: true}),
        count: removed
      }));
    }

    return {
      action: punctuateSeries(actions),
      count: added.length + removed.length
    };
  }
}
