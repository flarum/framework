import Component from '../../common/Component';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Discussion from '../../common/models/Discussion';

/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component {
  view() {
    const state = this.attrs.state;
    const pages = state.getNotificationPages();

    return (
      <div className="NotificationList">
        <div className="NotificationList-header">
          <div className="App-primaryControl">
            {Button.component({
              className: 'Button Button--icon Button--link',
              icon: 'fas fa-check',
              title: app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip'),
              onclick: state.markAllAsRead.bind(state),
            })}
          </div>

          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('core.forum.notifications.title')}</h4>
        </div>

        <div className="NotificationList-content">
          {pages.length
            ? pages.map((notifications) => {
                const groups = [];
                const discussions = {};

                notifications.forEach((notification) => {
                  const subject = notification.subject();

                  if (typeof subject === 'undefined') return;

                  // Get the discussion that this notification is related to. If it's not
                  // directly related to a discussion, it may be related to a post or
                  // other entity which is related to a discussion.
                  let discussion = false;
                  if (subject instanceof Discussion) discussion = subject;
                  else if (subject && subject.discussion) discussion = subject.discussion();

                  // If the notification is not related to a discussion directly or
                  // indirectly, then we will assign it to a neutral group.
                  const key = discussion ? discussion.id() : 0;
                  discussions[key] = discussions[key] || { discussion: discussion, notifications: [] };
                  discussions[key].notifications.push(notification);

                  if (groups.indexOf(discussions[key]) === -1) {
                    groups.push(discussions[key]);
                  }
                });

                return groups.map((group) => {
                  const badges = group.discussion && group.discussion.badges().toArray();

                  return (
                    <div className="NotificationGroup">
                      {group.discussion ? (
                        <Link className="NotificationGroup-header" href={app.route.discussion(group.discussion)}>
                          {badges && badges.length ? <ul className="NotificationGroup-badges badges">{listItems(badges)}</ul> : ''}
                          {group.discussion.title()}
                        </Link>
                      ) : (
                        <div className="NotificationGroup-header">{app.forum.attribute('title')}</div>
                      )}

                      <ul className="NotificationGroup-content">
                        {group.notifications.map((notification) => {
                          const NotificationComponent = app.notificationComponents[notification.contentType()];
                          return NotificationComponent ? <li>{NotificationComponent.component({ notification })}</li> : '';
                        })}
                      </ul>
                    </div>
                  );
                });
              })
            : ''}
          {state.isLoading() ? (
            <LoadingIndicator className="LoadingIndicator--block" />
          ) : pages.length ? (
            ''
          ) : (
            <div className="NotificationList-empty">{app.translator.trans('core.forum.notifications.empty_text')}</div>
          )}
        </div>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$notifications = this.$('.NotificationList-content');
    this.$scrollParent = this.$notifications.css('overflow') === 'auto' ? this.$notifications : $(window);

    this.boundScrollHandler = this.scrollHandler.bind(this);
    this.$scrollParent.on('scroll', this.boundScrollHandler);
  }

  onremove() {
    this.$scrollParent.off('scroll', this.boundScrollHandler);
  }

  scrollHandler() {
    const state = this.attrs.state;

    const scrollTop = this.$scrollParent.scrollTop();
    const viewportHeight = this.$scrollParent.height();

    const contentTop = this.$scrollParent === this.$notifications ? 0 : this.$notifications.offset().top;
    const contentHeight = this.$notifications[0].scrollHeight;

    if (state.hasMoreResults() && !state.isLoading() && scrollTop + viewportHeight >= contentTop + contentHeight) {
      state.loadMore();
    }
  }
}
