import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import highlight from '../../common/helpers/highlight';
import LinkButton from '../../common/components/LinkButton';
import { PostProp } from '../../common/concerns/ComponentProps';

/**
 * The `PostPreview` component shows a link to a post containing the avatar and
 * username of the author, and a short excerpt of the post's content.
 */
export default class PostPreview<T extends PostProp = PostProp> extends Component<T> {
    view() {
        const post = this.props.post;
        const user = post.user();
        const excerpt = highlight(post.contentPlain(), this.props.highlight, 300);

        return (
            <LinkButton className="PostPreview" href={app.route.post(post)} onclick={this.props.onclick}>
                <span className="PostPreview-content">
                    {avatar(user)}
                    {username(user)} <span className="PostPreview-excerpt">{excerpt}</span>
                </span>
            </LinkButton>
        );
    }
}
