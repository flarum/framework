import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import listItems from '../../common/helpers/listItems';
import highlight from '../../common/helpers/highlight';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import abbreviateNumber from '../../common/utils/abbreviateNumber';
import Dropdown from '../../common/components/Dropdown';
import TerminalPost from './TerminalPost';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import DiscussionControls from '../utils/DiscussionControls';
import slidable from '../utils/slidable';
import classList from '../../common/utils/classList';
import DiscussionPage from './DiscussionPage';
import escapeRegExp from '../../common/utils/escapeRegExp';
import Tooltip from '../../common/components/Tooltip';
import type Discussion from '../../common/models/Discussion';
import type Mithril from 'mithril';
import type { DiscussionListParams } from '../states/DiscussionListState';
import Icon from '../../common/components/Icon';
import Avatar from '../../common/components/Avatar';

export interface IDiscussionListItemAttrs extends ComponentAttrs {
  discussion: Discussion;
  params: DiscussionListParams;
}

/**
 * The `DiscussionListItem` component shows a single discussion in the
 * discussion list.
 */
export default class DiscussionListItem<CustomAttrs extends IDiscussionListItemAttrs = IDiscussionListItemAttrs> extends Component<CustomAttrs> {
  /**
   * Ensures that the discussion will not be redrawn
   * unless new data comes in.
   */
  subtree!: SubtreeRetainer;

  highlightRegExp?: RegExp;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

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
      className: classList('DiscussionListItem', this.attrs.className, {
        active: this.active(),
        'DiscussionListItem--hidden': this.attrs.discussion.isHidden(),
        Slidable: 'ontouchstart' in window,
      }),
    };
  }

  view() {
    const attrs = this.elementAttrs();

    return <div {...attrs}>{this.viewItems().toArray()}</div>;
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const controls = DiscussionControls.controls(this.attrs.discussion, this).toArray();

    if (controls.length) {
      items.add('controls', this.controlsView(controls), 100);
    }

    items.add('slidableUnderneath', this.slidableUnderneathView(), 90);
    items.add('content', this.contentView(), 80);

    return items;
  }

  controlsView(controls: Mithril.ChildArray): Mithril.Children {
    return (
      <Dropdown
        icon="fas fa-ellipsis-v"
        className="DiscussionListItem-controls"
        buttonClassName="Button Button--icon Button--flat Slidable-underneath Slidable-underneath--right"
        accessibleToggleLabel={app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label')}
      >
        {controls}
      </Dropdown>
    );
  }

  slidableUnderneathView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const isUnread = discussion.isUnread();

    return (
      <span
        className={classList('Slidable-underneath Slidable-underneath--left Slidable-underneath--elastic', { disabled: !isUnread })}
        onclick={this.markAsRead.bind(this)}
      >
        <Icon name={'fas fa-check'} />
      </span>
    );
  }

  contentView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const isUnread = discussion.isUnread();
    const isRead = discussion.isRead();

    return (
      <div className={classList('DiscussionListItem-content', 'Slidable-content', { unread: isUnread, read: isRead })}>
        {this.contentItems().toArray()}
      </div>
    );
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('author', this.authorView(), 100);
    items.add('main', this.mainView(), 80);
    items.add('stats', this.statsView(), 70);

    return items;
  }

  authorView(): Mithril.Children {
    return <div className="DiscussionListItem-author">{this.authorItems().toArray()}</div>;
  }

  authorItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const discussion = this.attrs.discussion;
    const user = discussion.user();

    items.add(
      'avatar',
      <Tooltip
        text={app.translator.trans('core.forum.discussion_list.started_text', { user, ago: humanTime(discussion.createdAt()) })}
        position="right"
      >
        <Link className="DiscussionListItem-author-avatar" href={user ? app.route.user(user) : '#'}>
          <Avatar user={user || null} title="" />
        </Link>
      </Tooltip>,
      100
    );

    items.add('badges', this.badgesView(), 90);

    return items;
  }

  badgesView(): Mithril.Children {
    const discussion = this.attrs.discussion;

    return <ul className="DiscussionListItem-badges badges badges--packed">{listItems(discussion.badges().toArray())}</ul>;
  }

  mainView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const jumpTo = this.getJumpTo();

    return (
      <Link href={app.route.discussion(discussion, jumpTo)} className="DiscussionListItem-main">
        <h2 className="DiscussionListItem-title">{highlight(discussion.title(), this.highlightRegExp)}</h2>
        <ul className="DiscussionListItem-info">{listItems(this.infoItems().toArray())}</ul>
      </Link>
    );
  }

  getJumpTo() {
    const discussion = this.attrs.discussion;
    let jumpTo = 0;

    if (this.attrs.params.q) {
      const post = discussion.mostRelevantPost();
      if (post) {
        jumpTo = post.number();
      }

      const phrase = escapeRegExp(this.attrs.params.q);
      this.highlightRegExp = new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');
    } else {
      jumpTo = Math.min(discussion.lastPostNumber() ?? 0, (discussion.lastReadPostNumber() || 0) + 1);
    }

    return jumpTo;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // If we're on a touch device, set up the discussion row to be slidable.
    // This allows the user to drag the row to either side of the screen to
    // reveal controls.
    if ('ontouchstart' in window) {
      const slidableInstance = slidable(this.element);

      this.$('.DiscussionListItem-controls').on('hidden.bs.dropdown', () => slidableInstance.reset());
    }
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeupdate(vnode);

    return this.subtree.needsRebuild();
  }

  /**
   * Determine whether or not the discussion is currently being viewed.
   */
  active() {
    return app.current.matches(DiscussionPage, { discussion: this.attrs.discussion });
  }

  /**
   * Determine whether or not information about who started the discussion
   * should be displayed instead of information about the most recent reply to
   * the discussion.
   */
  showFirstPost() {
    return ['newest', 'oldest'].includes(this.attrs.params.sort ?? '');
  }

  /**
   * Determine whether or not the number of replies should be shown instead of
   * the number of unread posts.
   *
   * @return {boolean}
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
   */
  infoItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.attrs.params.q) {
      const post = this.attrs.discussion.mostRelevantPost() || this.attrs.discussion.firstPost();

      if (post && post.contentType() === 'comment') {
        const excerpt = highlight(post.contentPlain() ?? '', this.highlightRegExp, 175);
        items.add('excerpt', excerpt, -100);
      }
    } else {
      items.add('terminalPost', <TerminalPost discussion={this.attrs.discussion} lastPost={!this.showFirstPost()} />);
    }

    return items;
  }

  statsView(): Mithril.Children {
    return <div className="DiscussionListItem-stats">{this.statsItems().toArray()}</div>;
  }

  statsItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('replyCount', this.replyCountItem(), 70);

    return items;
  }

  replyCountItem() {
    const discussion = this.attrs.discussion;
    const showUnread = !this.showRepliesCount() && discussion.isUnread();
    const a11yKey = showUnread ? 'core.forum.discussion_list.unread_replies_a11y_label' : 'core.forum.discussion_list.total_replies_a11y_label';

    return (
      <DiscussionListItemStatsItem
        className="DiscussionListItem-count"
        icon={showUnread ? [<Icon name={'fas fa-check _checkmark'} />, <Icon name={'fas fa-comment _comment'} />] : <Icon name={'far fa-comment'} />}
        label={showUnread ? abbreviateNumber(discussion.unreadCount()) : abbreviateNumber(discussion.replyCount())}
        a11yLabel={app.translator.trans(a11yKey, { count: discussion.replyCount() })}
        onclick={showUnread ? this.markAsRead.bind(this) : undefined}
      />
    );
  }
}

export interface DiscussionListItemStatsItemAttrs extends ComponentAttrs {
  icon: string;
  label: string;
  a11yLabel?: string;
}

export class DiscussionListItemStatsItem extends Component<DiscussionListItemStatsItemAttrs> {
  view(vnode: Mithril.Vnode<DiscussionListItemStatsItemAttrs>) {
    const { icon, label, a11yLabel, className, ...attrs } = vnode.attrs;
    const Tag = attrs.onclick ? 'button' : 'span';

    return (
      <Tag className={classList('DiscussionListItem-stats-item', className, { 'Button--ua-reset': Tag === 'button' })} {...attrs}>
        <span className="DiscussionListItem-stats-item-icon">{icon}</span>
        <span className="DiscussionListItem-stats-item-label">
          <span aria-hidden="true">{label}</span>
          <span className="visually-hidden">{a11yLabel ?? label}</span>
        </span>
      </Tag>
    );
  }
}
