import Component from 'flarum/Component';
import UserCard from 'flarum/components/UserCard';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import listItems from 'flarum/helpers/listItems';

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 *
 * ### Props
 *
 * - `post`
 */
export default class PostHeaderUser extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the user hover card is visible.
     *
     * @type {Boolean}
     */
    this.cardVisible = false;
  }

  view() {
    const post = this.props.post;
    const user = post.user();

    if (!user) {
      return (
        <div className="post-user">
          <h3>{avatar(user)} {username(user)}</h3>
        </div>
      );
    }

    let card = '';

    if (!post.isHidden() && this.cardVisible) {
      card = UserCard.component({
        user,
        className: 'user-card-popover fade',
        controlsButtonClassName: 'btn btn-default btn-icon btn-controls btn-naked'
      });
    }

    return (
      <div className="post-user">
        <h3>
          <a href={app.route.user(user)} config={m.route}>
            {avatar(user)} {username(user)}
          </a>
        </h3>
        <ul className="badges">
          {listItems(user.badges().toArray())}
        </ul>
        {card}
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    let timeout;

    this.$()
      .on('mouseover', 'h3 a, .user-card', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.showCard.bind(this), 500);
      })
      .on('mouseout', 'h3 a, .user-card', () => {
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

    setTimeout(() => this.$('.user-card').addClass('in'));
  }

  /**
   * Hide the user card.
   */
  hideCard() {
    this.$('.user-card').removeClass('in')
      .one('transitionend', () => {
        this.cardVisible = false;
        m.redraw();
      });
  }
}
