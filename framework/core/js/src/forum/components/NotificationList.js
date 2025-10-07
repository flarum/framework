import app from '../../forum/app';
import Component from '../../common/Component';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Discussion from '../../common/models/Discussion';
import ItemList from '../../common/utils/ItemList';
import Tooltip from '../../common/components/Tooltip';

/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component {
  view() {
    return <div className="NotificationList">{this.viewItems().toArray()}</div>;
  }

  viewItems() {
    const state = this.attrs.state;
    const items = new ItemList();

    items.add('header', <div className="NotificationList-header">{this.headerItems().toArray()}</div>, 100);

    items.add('content', <div className="NotificationList-content">{this.content(state)}</div>, 90);

    return items;
  }

  headerItems() {
    const items = new ItemList();

    items.add('title', <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('core.forum.notifications.title')}</h4>, 100);
    items.add('controls', <div className="App-primaryControl">{this.controlItems().toArray()}</div>, 90);

    return items;
  }

  controlItems() {
    const items = new ItemList();
    const state = this.attrs.state;

    items.add(
      'mark_all_as_read',
      <Tooltip text={app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip')}>
        <Button
          className="Button Button--link"
          data-container=".NotificationList"
          icon="fas fa-check"
          title={app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip')}
          onclick={state.markAllAsRead.bind(state)}
        />
      </Tooltip>,
      70
    );

    items.add(
      'delete_all',
      <Tooltip text={app.translator.trans('core.forum.notifications.delete_all_tooltip')}>
        <Button
          className="Button Button--link"
          data-container=".NotificationList"
          icon="fas fa-trash-alt"
          title={app.translator.trans('core.forum.notifications.delete_all_tooltip')}
          onclick={() => {
            if (confirm(app.translator.trans('core.forum.notifications.delete_all_confirm'))) {
              state.deleteAll.call(state);
            }
          }}
        />
      </Tooltip>,
      50
    );

    return items;
  }

  content(state) {
    if (state.isLoading()) {
      return <LoadingIndicator className="LoadingIndicator--block" />;
    }

    if (!state.hasItems()) {
      return <div className="NotificationList-empty">{app.translator.trans('core.forum.notifications.empty_text')}</div>;
    }

    return state.getPages().flatMap((page) => this.pageItems(page).toArray());
  }

  pageItems(page) {
    const items = new ItemList();

    const groups = this.buildGroups(page);

    groups.forEach((group, index) => {
      items.add(`group-${index}`, this.groupView(group), -index);
    });

    return items;
  }

  buildGroups(page) {
    const groups = [];
    const discussions = {};

    page.items.forEach((notification) => {
      const subject = notification.subject();
      if (typeof subject === 'undefined') return;

      // Get the discussion that this notification is related to. If it's not
      // directly related to a discussion, it may be related to a post or
      // other entity which is related to a discussion.
      let discussion = null;
      if (subject instanceof Discussion) discussion = subject;
      else if (subject && subject.discussion) discussion = subject.discussion();

      // If the notification is not related to a discussion directly or
      // indirectly, then we will assign it to a neutral group.
      const key = discussion ? discussion.id() : 0;
      discussions[key] = discussions[key] || { discussion, notifications: [] };
      discussions[key].notifications.push(notification);

      if (groups.indexOf(discussions[key]) === -1) {
        groups.push(discussions[key]);
      }
    });

    return groups;
  }

  groupKey(group, fallbackIndex) {
    return group.discussion ? group.discussion.id() : `neutral-${fallbackIndex}`;
  }

  groupView(group) {
    const badges = group.discussion && group.discussion.badges().toArray();

    const items = this.groupItems(group, badges).toArray();

    return <div className="NotificationGroup">{items}</div>;
  }

  groupItems(group, badges) {
    const items = new ItemList();

    items.add('header', this.groupHeaderItems(group, badges).toArray()[0], 100);

    items.add('body', this.groupBodyItems(group).toArray()[0], 90);

    return items;
  }

  groupHeaderItems(group, badges) {
    const items = new ItemList();

    if (group.discussion) {
      items.add(
        'discussion',
        <Link className="NotificationGroup-header" href={app.route.discussion(group.discussion)}>
          {badges && !!badges.length && <ul className="NotificationGroup-badges badges">{listItems(badges)}</ul>}
          <span>{group.discussion.title()}</span>
        </Link>,
        100
      );
    } else {
      items.add('neutral', <div className="NotificationGroup-header">{this.groupTitle(group)}</div>, 0);
    }

    return items;
  }

  groupBodyItems(group) {
    const items = new ItemList();

    items.add(
      'list',
      <ul className="NotificationGroup-content">{group.notifications.map((n, i) => this.notificationItem(n, i)).filter(Boolean)}</ul>,
      100
    );

    return items;
  }

  notificationItem(notification) {
    const NotificationComponent = app.notificationComponents[notification.contentType()];
    if (!NotificationComponent) return null;

    return (
      <li>
        <NotificationComponent notification={notification} />
      </li>
    );
  }

  groupTitle(group) {
    return app.forum.attribute('title');
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$notifications = this.$('.NotificationList-content');

    // If we are on the notifications page, the window will be scrolling and not the $notifications element.
    this.$scrollParent = this.inPanel() ? this.$notifications : $(window);

    this.boundScrollHandler = this.scrollHandler.bind(this);
    this.$scrollParent.on('scroll', this.boundScrollHandler);
  }

  onremove(vnode) {
    super.onremove(vnode);

    this.$scrollParent.off('scroll', this.boundScrollHandler);
  }

  scrollHandler() {
    const state = this.attrs.state;

    // Whole-page scroll events are listened to on `window`, but we need to get the actual
    // scrollHeight, scrollTop, and clientHeight from the document element.
    const scrollParent = this.inPanel() ? this.$scrollParent[0] : document.documentElement;

    // On very short screens, the scrollHeight + scrollTop might not reach the clientHeight
    // by a fraction of a pixel, so we compensate for that.
    const atBottom = Math.abs(scrollParent.scrollHeight - scrollParent.scrollTop - scrollParent.clientHeight) <= 1;

    if (state.hasNext() && !state.isLoadingNext() && atBottom) {
      state.loadNext();
    }
  }

  /**
   * If the NotificationList component isn't in a panel (e.g. on NotificationPage when mobile),
   * we need to listen to scroll events on the window, and get scroll state from the body.
   */
  inPanel() {
    return this.$notifications.css('overflow') === 'auto';
  }
}
