import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';
import IPAddress from '../../common/components/IPAddress';
import Post from '../../common/models/Post';
import type Model from '../../common/Model';
import type User from '../../common/models/User';
import classList from '../../common/utils/classList';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

type ModelType =
  | Post
  | (Model & { user: () => User | null | false; createdAt: () => Date; ipAddress: undefined | (() => string | null | undefined) });

export interface IPostMetaAttrs extends ComponentAttrs {
  /** Can be a post or similar model like private message */
  post: ModelType;
  permalink?: () => string;
}

/**
 * The `PostMeta` component displays the time of a post, and when clicked, shows
 * a dropdown containing more information about the post (number, full time,
 * permalink).
 */
export default class PostMeta<CustomAttrs extends IPostMetaAttrs = IPostMetaAttrs> extends Component<CustomAttrs> {
  view() {
    return <div className="Dropdown PostMeta">{this.viewItems().toArray()}</div>;
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const post = this.attrs.post;
    const permalink = this.getPermalink(post);
    const time = post.createdAt();

    items.add(
      'time',
      <button
        type="button"
        className={classList({
          'Button Button--text': true,
          'Dropdown-toggle Button--link': !!permalink,
        })}
        onclick={permalink ? this.selectPermalink.bind(this) : undefined}
        data-toggle="dropdown"
      >
        {humanTime(time)}
      </button>,
      100
    );

    items.add('meta-dropdown', !!permalink && <div className="Dropdown-menu dropdown-menu">{this.metaItems().toArray()}</div>, 90);

    return items;
  }

  metaItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const post = this.attrs.post;
    const time = post.createdAt();
    const permalink = this.getPermalink(post);
    const touch = 'ontouchstart' in document.documentElement;

    items.add('post-number', <span className="PostMeta-number">{this.postIdentifier(post)}</span>, 100);

    items.add('post-time', <span className="PostMeta-time">{fullTime(time)}</span>, 90);

    items.add(
      'post-ip',
      <span className="PostMeta-ip">
        <IPAddress ip={post.ipAddress?.()} />
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
        <input className="FormControl PostMeta-permalink" value={permalink} onclick={(e: MouseEvent) => e.stopPropagation()} />
      ),
      0
    );

    return items;
  }

  /**
   * Get the permalink for the given post.
   */
  getPermalink(post: ModelType): null | string {
    if (post instanceof Post) {
      return app.forum.attribute('baseOrigin') + app.route.post(post);
    }

    return this.attrs.permalink?.() || null;
  }

  /**
   * Selects the permalink input when the dropdown is shown.
   */
  selectPermalink(e: MouseEvent) {
    const $button = $(e.currentTarget as HTMLElement);
    setTimeout(() => $button.parent().find('.PostMeta-permalink').select());
    e.redraw = false;
  }

  postIdentifier(post: ModelType): string | null {
    if (post instanceof Post) {
      return app.translator.trans('core.forum.post.number_tooltip', { number: post.number() }, true);
    }

    return null;
  }
}
