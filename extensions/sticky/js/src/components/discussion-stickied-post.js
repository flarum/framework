import EventPost from 'flarum/components/event-post';

export default class DiscussionStickiedPost extends EventPost {
  view() {
    var post = this.props.post;

    return super.view('thumb-tack', [post.content().sticky ? 'stickied' : 'unstickied', ' the discussion.']);
  }
}
