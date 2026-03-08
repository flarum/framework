import EventPost from 'flarum/forum/components/EventPost';
import tagsLabel from '../../common/helpers/tagsLabel';

export default class DiscussionTaggedPost extends EventPost {
  oninit(vnode) {
    super.oninit(vnode);

    const oldTags = this.attrs.post.content()[0];
    const newTags = this.attrs.post.content()[1];

    this.tagsAdded = [];
    this.tagsRemoved = [];

    this.fetchRequired = false;
    this.loading = true;

    const tagsMutation = [...this.diffTags(newTags, oldTags), ...this.diffTags(oldTags, newTags)];
    if (tagsMutation.includes(undefined)) {
      this.fetchRequired = true;
    }

    const afterFetch = () => {
      this.tagsAdded = this.diffTags(newTags, oldTags);
      this.tagsRemoved = this.diffTags(oldTags, newTags);

      this.loading = false;
      m.redraw();
    };

    if (this.fetchRequired) {
      app.store
        .find('tags')
        .then(afterFetch)
        .catch(() => {
          this.loading = false;
          m.redraw();
        });
    } else {
      afterFetch();
    }
  }

  diffTags(tags1, tags2) {
    return tags1.filter((tag) => tags2.indexOf(tag) === -1).map((id) => app.store.getById('tags', id));
    // .filter(Boolean);
  }

  icon() {
    return 'fas fa-tag';
  }

  descriptionKey() {
    if (this.tagsAdded.length) {
      if (this.tagsRemoved.length) {
        return 'flarum-tags.forum.post_stream.added_and_removed_tags_text';
      }

      return 'flarum-tags.forum.post_stream.added_tags_text';
    }

    return 'flarum-tags.forum.post_stream.removed_tags_text';
  }

  descriptionData() {
    const data = {};

    if (this.tagsAdded.length) {
      data.tagsAdded = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: tagsLabel(this.tagsAdded, { link: true }),
        count: this.tagsAdded.length,
      });
    }

    if (this.tagsRemoved.length) {
      data.tagsRemoved = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: tagsLabel(this.tagsRemoved, { link: true }),
        count: this.tagsRemoved.length,
      });
    }

    return data;
  }
}
