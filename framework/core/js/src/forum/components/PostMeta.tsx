import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';
import IPAddress from '../../common/components/IPAddress';
import Post from '../../common/models/Post';
import type Model from '../../common/Model';
import type User from '../../common/models/User';
import classList from '../../common/utils/classList';

type ModelType = Post | (Model & { user: () => User | null | false; createdAt: () => Date });

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
    const post = this.attrs.post;
    const time = post.createdAt();
    const permalink = this.getPermalink(post);
    const touch = 'ontouchstart' in document.documentElement;

    // When the dropdown menu is shown, select the contents of the permalink
    // input so that the user can quickly copy the URL.
    const selectPermalink = function (this: Element, e: MouseEvent) {
      setTimeout(() => $(this).parent().find('.PostMeta-permalink').select());

      e.redraw = false;
    };

    return (
      <div className="Dropdown PostMeta">
        <button
          className={classList({
            'Button Button--text': true,
            'Dropdown-toggle Button--link': !!permalink,
          })}
          onclick={permalink ? selectPermalink : undefined}
          data-toggle="dropdown"
        >
          {humanTime(time)}
        </button>

        {!!permalink && (
          <div className="Dropdown-menu dropdown-menu">
            <span className="PostMeta-number">{this.postIdentifier(post)}</span> <span className="PostMeta-time">{fullTime(time)}</span>{' '}
            <span className="PostMeta-ip">
              <IPAddress ip={post.data.attributes?.ipAddress} />
            </span>
            {touch ? (
              <a className="Button PostMeta-permalink" href={permalink}>
                {permalink}
              </a>
            ) : (
              <input className="FormControl PostMeta-permalink" value={permalink} onclick={(e: MouseEvent) => e.stopPropagation()} />
            )}
          </div>
        )}
      </div>
    );
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

  postIdentifier(post: ModelType): string | null {
    if (post instanceof Post) {
      return app.translator.trans('core.forum.post.number_tooltip', { number: post.number() }, true);
    }

    return null;
  }
}
