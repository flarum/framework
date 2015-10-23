import EventPost from 'flarum/components/EventPost';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import tagsLabel from 'flarum/tags/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  icon() {
    return 'tag';
  }

  // NEED TO FIX:
  // This should return one of three strings, depending on whether tags are added, removed, or both:
  //   if added: app.translator.trans('flarum-tags.forum.post_stream.added_tags_text')
  //   if removed: app.translator.trans('flarum-tags.forum.post_stream.removed_tags_text')
  //   if both: app.translator.trans('flarum-tags.forum.post_stream.added_and_removed_tags_text')
  // The 'flarum-tags.forum.discussion_tagged_post' key has been removed from the YAML.
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

    // PLEASE CHECK:
    // Both {addedTags} and {removedTags} in the above three strings can be returned using the same key.
    // The key names has been changed ... Is it possible to combine these two operations?
    if (added.length) {
      actions.push(app.translator.transChoice('flarum-tags.forum.post_stream.tags_text', added, {
        tags: tagsLabel(added, {link: true}),
        count: added
      }));
    }

    if (removed.length) {
      actions.push(app.translator.transChoice('flarum-tags.forum.post_stream.tags_text', removed, {
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
