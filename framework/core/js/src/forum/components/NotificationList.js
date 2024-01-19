import app from '../../forum/app';
import Component from '../../common/Component';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import Discussion from '../../common/models/Discussion';
import ItemList from '../../common/utils/ItemList';
import Tooltip from '../../common/components/Tooltip';
import HeaderList from './HeaderList';
import HeaderListGroup from './HeaderListGroup';

/**
 * The `NotificationList` component displays a list of the logged-in user's
 * notifications, grouped by discussion.
 */
export default class NotificationList extends Component {
  view() {
    const state = this.attrs.state;

    return (
      <HeaderList
        className="NotificationList"
        title={app.translator.trans('core.forum.notifications.title')}
        controls={this.controlItems()}
        hasItems={state.hasItems()}
        loading={state.isLoading()}
        emptyText={app.translator.trans('core.forum.notifications.empty_text')}
        loadMore={() => state.hasNext() && !state.isLoadingNext() && state.loadNext()}
      >
        {this.content(state)}
      </HeaderList>
    );
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
    if (!state.isLoading() && state.hasItems()) {
      return state.getPages().map((page) => {
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
          discussions[key] = discussions[key] || { discussion: discussion, notifications: [] };
          discussions[key].notifications.push(notification);

          if (groups.indexOf(discussions[key]) === -1) {
            groups.push(discussions[key]);
          }
        });

        return groups.map((group) => {
          const badges = group.discussion && group.discussion.badges().toArray();

          return (
            <HeaderListGroup
              label={
                group.discussion ? (
                  <Link href={app.route.discussion(group.discussion)}>
                    {badges && !!badges.length && <ul className="HeaderListGroup-badges badges">{listItems(badges)}</ul>}
                    <span>{group.discussion.title()}</span>
                  </Link>
                ) : (
                  app.forum.attribute('title')
                )
              }
            >
              {group.notifications
                .map((notification) => {
                  const NotificationComponent = app.notificationComponents[notification.contentType()];
                  return !!NotificationComponent ? <NotificationComponent notification={notification} /> : null;
                })
                .filter((component) => !!component)}
            </HeaderListGroup>
          );
        });
      });
    }

    return null;
  }
}
