import Component from '../../common/Component';
import UserCard from './UserCard';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import { PostProp } from '../../common/concerns/ComponentProps';

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 */
export default class PostUser extends Component<PostProp> {
    /**
     * Whether or not the user hover card is visible.
     */
    cardVisible = false;

    view() {
        const post = this.props.post;
        const user = post.user();

        if (!user) {
            return (
                <div className="PostUser">
                    <h3>
                        {avatar(user, { className: 'PostUser-avatar' })} {username(user)}
                    </h3>
                </div>
            );
        }

        let card = '';

        if (!post.isHidden() && this.cardVisible) {
            card = UserCard.component({
                user,
                className: 'UserCard--popover',
                controlsButtonClassName: 'Button Button--icon Button--flat',
            });
        }

        return (
            <div className="PostUser">
                <h3>
                    <m.route.Link href={app.route.user(user)}>
                        {avatar(user, { className: 'PostUser-avatar' })}
                        {userOnline(user)}
                        {username(user)}
                    </m.route.Link>
                </h3>
                <ul className="PostUser-badges badges">{listItems(user.badges().toArray())}</ul>
                {card}
            </div>
        );
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        let timeout;

        this.$()
            .on('mouseover', 'h3 a, .UserCard', () => {
                clearTimeout(timeout);
                timeout = setTimeout(this.showCard.bind(this), 500);
            })
            .on('mouseout', 'h3 a, .UserCard', () => {
                clearTimeout(timeout);
                timeout = setTimeout(this.hideCard.bind(this), 250);
            });
    }

    /**
     * Show the user card.
     */
    showCard() {
        this.cardVisible = true;

        m.redraw();

        setTimeout(() => this.$('.UserCard').addClass('in'));
    }

    /**
     * Hide the user card.
     */
    hideCard() {
        this.$('.UserCard')
            .removeClass('in')
            .one('transitionend webkitTransitionEnd oTransitionEnd', () => {
                this.cardVisible = false;
                m.redraw();
            });
    }
}
