import PostActivity from 'flarum/components/post-activity';

export default class PostDiscussionStickied extends PostActivity {
  view() {
    var post = this.props.post;

    return super.view('thumb-tack', [post.content().sticky ? 'stickied' : 'unstickied', ' the discussion.']);
  }
}
