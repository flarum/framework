import EventPost from 'flarum/components/event-post';
import tagsLabel from 'flarum-tags/helpers/tags-label';

export default class DiscussionTaggedPost extends EventPost {
  view() {
    var post = this.props.post;
    var oldTags = post.content()[0];
    var newTags = post.content()[1];

    var added = newTags.filter(tag => oldTags.indexOf(tag) === -1).map(id => app.store.getById('tags', id));
    var removed = oldTags.filter(tag => newTags.indexOf(tag) === -1).map(id => app.store.getById('tags', id));
    var total = added.concat(removed);

    var build = function(verb, tags, only) {
      return tags.length ? [verb, ' ', only && tags.length == 1 ? 'the ' : '', tagsLabel(tags)] : '';
    };

    return super.view('tag', [
      build('added', added, !removed.length),
      added.length && removed.length ? ' and ' : '',
      build('removed', removed, !added.length),
      total.length ? (total.length == 1 ? ' tag.' : ' tags.') : ''
    ]);
  }
}
