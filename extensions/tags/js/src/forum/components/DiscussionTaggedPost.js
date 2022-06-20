import EventPost from 'flarum/forum/components/EventPost';
import tagsLabel from '../../common/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    const oldTags = attrs.post.content()[0];
    const newTags = attrs.post.content()[1];

    function diffTags(tags1, tags2) {
      return tags1.filter((tag) => tags2.indexOf(tag) === -1).map((id) => app.store.getById('tags', id));
    }

    attrs.tagsAdded = diffTags(newTags, oldTags);
    attrs.tagsRemoved = diffTags(oldTags, newTags);
  }

  icon() {
    return 'fas fa-tag';
  }

  descriptionKey() {
    if (this.attrs.tagsAdded.length) {
      if (this.attrs.tagsRemoved.length) {
        return 'flarum-tags.forum.post_stream.added_and_removed_tags_text';
      }

      return 'flarum-tags.forum.post_stream.added_tags_text';
    }

    return 'flarum-tags.forum.post_stream.removed_tags_text';
  }

  descriptionData() {
    const data = {};

    if (this.attrs.tagsAdded.length) {
      data.tagsAdded = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: tagsLabel(this.attrs.tagsAdded, { link: true }),
        count: this.attrs.tagsAdded.length,
      });
    }

    if (this.attrs.tagsRemoved.length) {
      data.tagsRemoved = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: tagsLabel(this.attrs.tagsRemoved, { link: true }),
        count: this.attrs.tagsRemoved.length,
      });
    }

    return data;
  }
}
