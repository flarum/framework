import Component from '../../common/Component';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
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
                        <a className="NotificationGroup-header" href={app.route.discussion(group.discussion)} config={m.route}>
                          {badges && badges.length ? <ul className="NotificationGroup-badges badges">{listItems(badges)}</ul> : ''}
                          {group.discussion.title()}
                        </a>
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

    const state = this.attrs.state;

    const $notifications = this.$('.NotificationList-content');
    const $scrollParent = $notifications.css('overflow') === 'auto' ? $notifications : $(window);

    const scrollHandler = () => {
      const scrollTop = $scrollParent.scrollTop();
      const viewportHeight = $scrollParent.height();
      const contentTop = $scrollParent === $notifications ? 0 : $notifications.offset().top;
      const contentHeight = $notifications[0].scrollHeight;

      if (state.hasMoreResults() && !state.isLoading() && scrollTop + viewportHeight >= contentTop + contentHeight) {
        state.loadMore();
      }
    };

    $scrollParent.on('scroll', scrollHandler);

    context.onunload = () => {
      $scrollParent.off('scroll', scrollHandler);
    };
  }
}
