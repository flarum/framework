import Component from '../../common/Component';
import { PostProp } from '../../common/concerns/ComponentProps';
import humanTime from '../../common/helpers/humanTime';
import fullTime from '../../common/helpers/fullTime';
import Post from '../../common/models/Post';

export default class PostMeta extends Component<PostProp> {
    view() {
        const post = this.props.post;
        const time = post.createdAt();
        const permalink = this.getPermalink(post);
        const touch = 'ontouchstart' in document.documentElement;

        // When the dropdown menu is shown, select the contents of the permalink
        // input so that the user can quickly copy the URL.
        const selectPermalink = function(this: HTMLElement) {
            setTimeout(() =>
                $(this)
                    .parent()
                    .find('.PostMeta-permalink')
                    .select()
            );
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
                        <input className="FormControl PostMeta-permalink" value={permalink} onclick={e => e.stopPropagation()} />
                    )}
                </div>
            </div>
        );
    }

    /**
     * Get the permalink for the given post.
     */
    getPermalink(post: Post): string {
        return window.location.origin + app.route.post(post);
    }
}
