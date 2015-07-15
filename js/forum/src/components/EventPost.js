import Post from 'flarum/components/Post';
import usernameHelper from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

/**
 * The `EventPost` component displays a post which indicating a discussion
 * event, like a discussion being renamed or stickied. Subclasses must implement
 * the `icon` and `description` methods.
 *
 * ### Props
 *
 * - All of the props for `Post`
 *
 * @abstract
 */
export default class EventPost extends Post {
  attrs() {
    return {
      className: 'event-post ' + this.props.post.contentType() + '-post'
    };
  }

  content() {
    const user = this.props.post.user();
    const username = usernameHelper(user);

    return [
      icon(this.icon(), {className: 'event-post-icon'}),
      <div class="event-post-info">
        {user ? <a className="post-user" href={app.route.user(user)} config={m.route}>{username}</a> : username}
        {this.description()}
      </div>
    ];
  }

  /**
   * Get the name of the event icon.
   *
   * @return {String}
   */
  icon() {
  }

  /**
   * Get the description of the event.
   *
   * @return {VirtualElement}
   */
  description() {
  }
}
