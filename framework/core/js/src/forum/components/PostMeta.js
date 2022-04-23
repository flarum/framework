import app from '../../forum/app';
import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';

/**
 * The `PostMeta` component displays the time of a post, and when clicked, shows
 * a dropdown containing more information about the post (number, full time,
 * permalink).
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostMeta extends Component {
  view() {
    const post = this.attrs.post;
    const time = post.createdAt();
    const permalink = this.getPermalink(post);
    const touch = 'ontouchstart' in document.documentElement;

    // When the dropdown menu is shown, select the contents of the permalink
    // input so that the user can quickly copy the URL.
    const selectPermalink = function (e) {
      setTimeout(() => $(this).parent().find('.PostMeta-permalink').select());

      e.redraw = false;
    };

    return (
      <div className="Dropdown PostMeta">
        <a className="Dropdown-toggle" onclick={selectPermalink} data-toggle="dropdown">
          {humanTime(time)}
        </a>

        <div className="Dropdown-menu dropdown-menu">
          <span className="PostMeta-number">{app.translator.trans('core.forum.post.number_tooltip', { number: post.number() })}</span>{' '}
          <span className="PostMeta-time">{fullTime(time)}</span> <span className="PostMeta-ip">{post.data.attributes.ipAddress}</span>
          {touch ? (
            <a className="Button PostMeta-permalink" href={permalink}>
              {permalink}
            </a>
          ) : (
            <input className="FormControl PostMeta-permalink" value={permalink} onclick={(e) => e.stopPropagation()} />
          )}
        </div>
      </div>
    );
  }

  /**
   * Get the permalink for the given post.
   *
   * @param {import('../../common/models/Post').default} post
   * @returns {string}
   */
  getPermalink(post) {
    return app.forum.attribute('baseOrigin') + app.route.post(post);
  }
}
