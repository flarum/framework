import app from '../../forum/app';
import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';
import ItemList from '../../common/utils/ItemList';
import IPAddress from '../../common/components/IPAddress';

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
    return <div className="Dropdown PostMeta">{this.viewItems().toArray()}</div>;
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

  /**
   * When the dropdown menu is shown, select the contents of the permalink
   * input so that the user can quickly copy the URL.
   * @param {Event} e
   */
  selectPermalink(e) {
    setTimeout(() => $(this.element).parent().find('.PostMeta-permalink').select());

    e.redraw = false;
  }

  /**
   * @returns {ItemList}
   */
  viewItems() {
    const items = new ItemList();
    const post = this.attrs.post;
    const time = post.createdAt();

    items.add(
      'time',
      <a className="Dropdown-toggle" onclick={(e) => this.selectPermalink(e)} data-toggle="dropdown">
        {humanTime(time)}
      </a>,
      100
    );

    items.add('meta-dropdown', <div className="Dropdown-menu dropdown-menu">{this.metaItems().toArray()}</div>, 90);

    return items;
  }

  /**
   * @returns {ItemList}
   */
  metaItems() {
    const items = new ItemList();
    const post = this.attrs.post;
    const touch = 'ontouchstart' in document.documentElement;
    const permalink = this.getPermalink(post);
    const time = post.createdAt();

    items.add(
      'post-number',
      <span className="PostMeta-number">{app.translator.trans('core.forum.post.number_tooltip', { number: post.number() })} </span>,
      100
    );

    items.add('post-time', <span className="PostMeta-time">{fullTime(time)}</span>, 90);

    items.add(
      'post-ip',
      <span className="PostMeta-ip">
        <IPAddress ip={post.data.attributes.ipAddress} />
      </span>,
      80
    );

    items.add(
      'permalink',
      touch ? (
        <a className="Button PostMeta-permalink" href={permalink}>
          {permalink}
        </a>
      ) : (
        <input className="FormControl PostMeta-permalink" value={permalink} onclick={(e) => e.stopPropagation()} />
      ),
      0
    );

    return items;
  }
}
