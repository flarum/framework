import app from '../../forum/app';
import Post from './Post';
import { ucfirst } from '../../common/utils/string';
import usernameHelper from '../../common/helpers/username';
import icon from '../../common/helpers/icon';
import Link from '../../common/components/Link';
import humanTime from '../../common/helpers/humanTime';
import classList from '../../common/utils/classList';

/**
 * The `EventPost` component displays a post which indicating a discussion
 * event, like a discussion being renamed or stickied. Subclasses must implement
 * the `icon` and `description` methods.
 *
 * ### Attrs
 *
 * - All of the attrs for `Post`
 *
 * @abstract
 */
export default class EventPost extends Post {
  elementAttrs() {
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className, 'EventPost', ucfirst(this.attrs.post.contentType()) + 'Post');

    return attrs;
  }

  content() {
    const user = this.attrs.post.user();
    const username = usernameHelper(user);
    const data = Object.assign(this.descriptionData(), {
      user,
      username: user ? (
        <Link className="EventPost-user" href={app.route.user(user)}>
          {username}
        </Link>
      ) : (
        username
      ),
      time: humanTime(this.attrs.post.createdAt()),
    });

    return super
      .content()
      .concat([icon(this.icon(), { className: 'EventPost-icon' }), <div className="EventPost-info">{this.description(data)}</div>]);
  }

  /**
   * Get the name of the event icon.
   *
   * @return {string}
   */
  icon() {
    return '';
  }

  /**
   * Get the description text for the event.
   *
   * @param {Record<string, unknown>} data
   * @return {import('mithril').Children} The description to render in the DOM
   */
  description(data) {
    return app.translator.trans(this.descriptionKey(), data);
  }

  /**
   * Get the translation key for the description of the event.
   *
   * @return {string}
   */
  descriptionKey() {
    return '';
  }

  /**
   * Get the translation data for the description of the event.
   *
   * @return {Record<string, unknown>}
   */
  descriptionData() {
    return {};
  }
}
