import EventPost from 'flarum/components/event-post';
import categoryLabel from 'flarum-categories/helpers/category-label';

export default class DiscussionMovedPost extends EventPost {
  view() {
    var post = this.props.post;
    var oldCategory = app.store.getById('categories', post.content()[0]);
    var newCategory = app.store.getById('categories', post.content()[1]);

    return super.view('arrow-right', ['moved the discussion from ', categoryLabel(oldCategory), ' to ', categoryLabel(newCategory), '.']);
  }
}
