import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/listItems';
import highlight from 'flarum/helpers/highlight';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/humanTime';
import ItemList from 'flarum/utils/ItemList';
import abbreviateNumber from 'flarum/utils/abbreviateNumber';
import Dropdown from 'flarum/components/Dropdown';
import TerminalPost from 'flarum/components/TerminalPost';
import PostPreview from 'flarum/components/PostPreview';
import SubtreeRetainer from 'flarum/utils/SubtreeRetainer';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import slidable from 'flarum/utils/slidable';

/**
 * The `DiscussionListItem` component shows a single discussion in the
 * discussion list.
 *
 * ### Props
 *
 * - `discussion`
 * - `params`
 */
export default class DiscussionListItem extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Set up a subtree retainer so that the discussion will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    this.subtree = new SubtreeRetainer(
      () => this.props.discussion.freshness,
      () => app.session.user && app.session.user.readTime(),
      () => this.active()
    );
  }

  view() {
    const discussion = this.props.discussion;
    const startUser = discussion.startUser();
    const isUnread = discussion.isUnread();
    const showUnread = !this.showRepliesCount() && isUnread;
    const jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);
    const relevantPosts = this.props.params.q ? discussion.relevantPosts() : '';
    const controls = DiscussionControls.controls(discussion, this).toArray();

    return this.subtree.retain() || (
      <div className={'discussion-list-item' + (this.active() ? ' active' : '')}>

        {controls.length ? Dropdown.component({
          icon: 'ellipsis-v',
          children: controls,
          className: 'contextual-controls',
          buttonClassName: 'btn btn-default btn-naked btn-controls slidable-underneath slidable-underneath-right',
          menuClassName: 'dropdown-menu-right'
        }) : ''}

        <a className={'slidable-underneath slidable-underneath-left elastic' + (isUnread ? '' : ' disabled')}
          onclick={this.markAsRead.bind(this)}>
          {icon('check', {className: 'icon'})}
        </a>

        <div className={'discussion-summary slidable-slider' + (isUnread ? ' unread' : '')}>
          <a href={startUser ? app.route.user(startUser) : '#'}
            className="author"
            title={'Started by ' + (startUser ? startUser.username() : '[deleted]') + ' ' + humanTime(discussion.startTime())}
            config={function(element) {
              $(element).tooltip({placement: 'right'});
              m.route.apply(this, arguments);
            }}>
            {avatar(startUser, {title: ''})}
          </a>

          <ul className="badges">{listItems(discussion.badges().toArray())}</ul>

          <a href={app.route.discussion(discussion, jumpTo)}
            config={m.route}
            className="main">
            <h3 className="title">{highlight(discussion.title(), this.props.params.q)}</h3>
            <ul className="info">{listItems(this.infoItems().toArray())}</ul>
          </a>

          <span className="count"
            onclick={this.markAsRead.bind(this)}
            title={showUnread ? 'Mark as Read' : ''}>
            {abbreviateNumber(discussion[showUnread ? 'unreadCount' : 'repliesCount']())}
          </span>

          {relevantPosts && relevantPosts.length
            ? <div className="relevant-posts">
                {relevantPosts.map(post => PostPreview.component({post, highlight: this.props.params.q}))}
              </div>
            : ''}

        </div>
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    // If we're on a touch device, set up the discussion row to be slidable.
    // This allows the user to drag the row to either side of the screen to
    // reveal controls.
    if ('ontouchstart' in window) {
      const slidableInstance = slidable(this.$().addClass('slidable'));

      this.$('.contextual-controls')
        .on('hidden.bs.dropdown', () => slidableInstance.reset());
    }
  }

  /**
   * Determine whether or not the discussion is currently being viewed.
   *
   * @return {Boolean}
   */
  active() {
    return m.route.param('id') === this.props.discussion.id();
  }

  /**
   * Determine whether or not information about who started the discussion
   * should be displayed instead of information about the most recent reply to
   * the discussion.
   *
   * @return {Boolean}
   */
  showStartPost() {
    return ['newest', 'oldest'].indexOf(this.props.params.sort) !== -1;
  }

  /**
   * Determine whether or not the number of replies should be shown instead of
   * the number of unread posts.
   *
   * @return {Boolean}
   */
  showRepliesCount() {
    return this.props.params.sort === 'replies';
  }

  /**
   * Mark the discussion as read.
   */
  markAsRead() {
    const discussion = this.props.discussion;

    if (discussion.isUnread()) {
      discussion.save({readNumber: discussion.lastPostNumber()});
      m.redraw();
    }
  }

  /**
   * Build an item list of info for a discussion listing. By default this is
   * just the first/last post indicator.
   *
   * @return {ItemList}
   */
  infoItems() {
    const items = new ItemList();

    items.add('terminalPost',
      TerminalPost.component({
        discussion: this.props.discussion,
        lastPost: !this.showStartPost()
      })
    );

    return items;
  }
}
