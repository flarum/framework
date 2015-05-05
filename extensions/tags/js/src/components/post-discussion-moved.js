import PostActivity from 'flarum/components/post-activity';
import categoryLabel from 'categories/helpers/category-label';

export default class PostDiscussionMoved extends PostActivity {
  view() {
    var post = this.props.post;
    var oldCategory = app.store.getById('categories', post.content()[0]);
    var newCategory = app.store.getById('categories', post.content()[1]);

    return super.view(['moved the discussion from ', categoryLabel(oldCategory), ' to ', categoryLabel(newCategory), '.'], {
      className: 'post-discussion-moved',
      icon: 'arrow-right'
    });
  }
}
