import PostActivity from 'flarum/components/post-activity';

export default class PostDiscussionRenamed extends PostActivity {
  view() {
    var post = this.props.post;
    var oldTitle = post.content()[0];
    var newTitle = post.content()[1];

    return super.view('pencil', ['changed the title from ', m('strong.old-title', oldTitle), ' to ', m('strong.new-title', newTitle), '.']);
  }
}
