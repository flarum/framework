import Component from '../../common/Component';

import Avatar from '../../common/components/Avatar';

/**
 * The `LoadingPost` component shows a placeholder that looks like a post,
 * indicating that the post is loading.
 */
export default class LoadingPost extends Component {
  view() {
    return (
      <div className="Post CommentPost LoadingPost">
        <header className="Post-header">
          <Avatar user={null} className="PostUser-avatar" />
          <div className="fakeText" />
        </header>

        <div className="Post-body">
          <div className="fakeText" />
          <div className="fakeText" />
          <div className="fakeText" />
        </div>
      </div>
    );
  }
}
