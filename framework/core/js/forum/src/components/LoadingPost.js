import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';

/**
 * The `LoadingPost` component shows a placeholder that looks like a post,
 * indicating that the post is loading.
 */
export default class LoadingPost extends Component {
  view() {
    return (
      <div className="post comment-post loading-post">
        <header className="post-header">
          {avatar()}
          <div className="fake-text"/>
        </header>

        <div className="post-body">
          <div className="fake-text"/>
          <div className="fake-text"/>
          <div className="fake-text"/>
        </div>
      </div>
    );
  }
}
