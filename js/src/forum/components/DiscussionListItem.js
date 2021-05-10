import Component from '../../common/Component';
import Link from '../../common/components/Link';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import highlight from '../../common/helpers/highlight';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import abbreviateNumber from '../../common/utils/abbreviateNumber';
import Dropdown from '../../common/components/Dropdown';
import TerminalPost from './TerminalPost';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import DiscussionControls from '../utils/DiscussionControls';
import slidable from '../utils/slidable';
import extractText from '../../common/utils/extractText';
import classList from '../../common/utils/classList';
import DiscussionPage from './DiscussionPage';
import escapeRegExp from '../../common/utils/escapeRegExp';
import Tooltip from '../../common/components/Tooltip';

/**
 * The `DiscussionListItem` component shows a single discussion in the
 * discussion list.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `params`
 */
export default class DiscussionListItem extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * Set up a subtree retainer so that the discussion will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    this.subtree = new SubtreeRetainer(
      () => this.attrs.discussion.freshness,
      () => {
        const time = app.session.user && app.session.user.markedAllAsReadAt();
        return time && time.getTime();
      },
      () => this.active()
    );
  }

  elementAttrs() {
    return {
      className: classList([
        'DiscussionListItem',
        this.active() ? 'active' : '',
        this.attrs.discussion.isHidden() ? 'DiscussionListItem--hidden' : '',
        'ontouchstart' in window ? 'Slidable' : '',
      ]),
    };
  }

  view() {
    const discussion = this.attrs.discussion;
    const user = discussion.user();
    const isUnread = discussion.isUnread();
    const isRead = discussion.isRead();
    const showUnread = !this.showRepliesCount() && isUnread;
    let jumpTo = 0;
    const controls = DiscussionControls.controls(discussion, this).toArray();
    const attrs = this.elementAttrs();

    if (this.attrs.params.q) {
      const post = discussion.mostRelevantPost();
      if (post) {
        jumpTo = post.number();
      }

      const phrase = escapeRegExp(this.attrs.params.q);
      this.highlightRegExp = new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');
    } else {
      jumpTo = Math.min(discussion.lastPostNumber(), (discussion.lastReadPostNumber() || 0) + 1);
    }

    return (
      <div {...attrs}>
        {controls.length
          ? Dropdown.component(
              {
                icon: 'fas fa-ellipsis-v',
                className: 'DiscussionListItem-controls',
                buttonClassName: 'Button Button--icon Button--flat Slidable-underneath Slidable-underneath--right',
                accessibleToggleLabel: app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label'),
              },
              controls
            )
          : ''}

        <span
          className={'Slidable-underneath Slidable-underneath--left Slidable-underneath--elastic' + (isUnread ? '' : ' disabled')}
          onclick={this.markAsRead.bind(this)}
        >
          {icon('fas fa-check')}
        </span>

        <div className={'DiscussionListItem-content Slidable-content' + (isUnread ? ' unread' : '') + (isRead ? ' read' : '')}>
          <Tooltip
            text={app.translator.trans('core.forum.discussion_list.started_text', { user, ago: humanTime(discussion.createdAt()) })}
            position="right"
          >
            <Link className="DiscussionListItem-author" href={user ? app.route.user(user) : '#'}>
              {avatar(user, { title: '' })}
            </Link>
          </Tooltip>

          <ul className="DiscussionListItem-badges badges">{listItems(discussion.badges().toArray())}</ul>

          <Link href={app.route.discussion(discussion, jumpTo)} className="DiscussionListItem-main">
            <h3 className="DiscussionListItem-title">{highlight(discussion.title(), this.highlightRegExp)}</h3>
            <ul className="DiscussionListItem-info">{listItems(this.infoItems().toArray())}</ul>
          </Link>

          <span
            tabindex="0"
            role="button"
            className="DiscussionListItem-count"
            onclick={this.markAsRead.bind(this)}
            title={showUnread ? app.translator.trans('core.forum.discussion_list.mark_as_read_tooltip') : ''}
          >
            {abbreviateNumber(discussion[showUnread ? 'unreadCount' : 'replyCount']())}
          </span>
        </div>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // If we're on a touch device, set up the discussion row to be slidable.
    // This allows the user to drag the row to either side of the screen to
    // reveal controls.
    if ('ontouchstart' in window) {
      const slidableInstance = slidable(this.$());

      this.$('.DiscussionListItem-controls').on('hidden.bs.dropdown', () => slidableInstance.reset());
    }
  }

  onbeforeupdate(vnode, old) {
    super.onbeforeupdate(vnode, old);

    return this.subtree.needsRebuild();
  }

  /**
   * Determine whether or not the discussion is currently being viewed.
   *
   * @return {Boolean}
   */
  active() {
    return app.current.matches(DiscussionPage, { discussion: this.attrs.discussion });
  }

  /**
   * Determine whether or not information about who started the discussion
   * should be displayed instead of information about the most recent reply to
   * the discussion.
   *
   * @return {Boolean}
   */
  showFirstPost() {
    return ['newest', 'oldest'].indexOf(this.attrs.params.sort) !== -1;
  }

  /**
   * Determine whether or not the number of replies should be shown instead of
   * the number of unread posts.
   *
   * @return {Boolean}
   */
  showRepliesCount() {
    return this.attrs.params.sort === 'replies';
  }

  /**
   * Mark the discussion as read.
   */
  markAsRead() {
    const discussion = this.attrs.discussion;

    if (discussion.isUnread()) {
      discussion.save({ lastReadPostNumber: discussion.lastPostNumber() });
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

    if (this.attrs.params.q) {
      const post = this.attrs.discussion.mostRelevantPost() || this.attrs.discussion.firstPost();

      if (post && post.contentType() === 'comment') {
        const excerpt = highlight(post.contentPlain(), this.highlightRegExp, 175);
        items.add('excerpt', excerpt, -100);
      }
    } else {
      items.add(
        'terminalPost',
        TerminalPost.component({
          discussion: this.attrs.discussion,
          lastPost: !this.showFirstPost(),
        })
      );
    }

    return items;
  }
}
