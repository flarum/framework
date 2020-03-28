import Post from './Post';
import { ucfirst } from '../../common/utils/string';
import usernameHelper from '../../common/helpers/username';
import icon from '../../common/helpers/icon';

interface DescriptionData {
    [key: string]: any;
}

/**
 * The `EventPost` component displays a post which indicating a discussion
 * event, like a discussion being renamed or stickied. Subclasses must implement
 * the `icon` and `description` methods.
 */
export default abstract class EventPost extends Post {
    attrs() {
        const attrs = super.attrs();

        attrs.className = classNames(attrs.className, 'EventPost', ucfirst(this.props.post.contentType()) + 'Post');

        return attrs;
    }

    content() {
        const user = this.props.post.user();
        const username = usernameHelper(user);
        const data: DescriptionData = Object.assign(this.descriptionData(), {
            user,
            username: user ? (
                <m.route.Link className="EventPost-user" href={app.route.user(user)}>
                    {username}
                </m.route.Link>
            ) : (
                username
            ),
        });

        return super
            .content()
            .concat([icon(this.icon(), { className: 'EventPost-icon' }), <div class="EventPost-info">{this.description(data)}</div>]);
    }

    /**
     * Get the name of the event icon.
     */
    abstract icon(): string;

    /**
     * Get the translation data for the description of the event.
     */
    abstract descriptionData(): DescriptionData;

    /**
     * Get the description text for the event.
     *
     * @return The description to render in the DOM
     */
    description(data: DescriptionData): any {
        return app.translator.transChoice(this.descriptionKey(), data.count, data);
    }

    /**
     * Get the translation key for the description of the event.
     */
    descriptionKey(): string {
        return '';
    }
}
