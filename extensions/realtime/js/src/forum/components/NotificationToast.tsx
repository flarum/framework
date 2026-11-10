import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type NotificationModel from 'flarum/common/models/Notification';
import NotificationType from 'flarum/forum/components/NotificationType';
import NotificationToastState from '../states/NotificationToastState';
import Discussion from 'flarum/common/models/Discussion';
import Link from 'flarum/common/components/Link';

export interface INotificationToastAttrs extends ComponentAttrs {
  state: NotificationToastState;
}

function toastDiscussion(notification: NotificationModel): InstanceType<typeof Discussion> | null {
  const subject = notification.subject();
  if (!subject) return null;
  if (subject instanceof Discussion) return subject;
  if (typeof (subject as any).discussion === 'function') {
    return (subject as any).discussion() ?? null;
  }
  return null;
}

/**
 * Renders the stack of realtime notification toasts in the top-right corner.
 * Each toast wraps the standard NotificationType component (same as the dropdown),
 * with the related discussion title shown as context above it.
 */
export default class NotificationToast extends Component<INotificationToastAttrs> {
  view(): Mithril.Children {
    const toasts = this.attrs.state.all();

    if (!toasts.length) return null;

    return (
      <div className="NotificationToasts" aria-live="polite" aria-label={app.translator.trans('core.forum.notifications.title')}>
        {toasts.map((entry) => {
          const discussion = toastDiscussion(entry.notification);

          return (
            <div key={entry.id} className="NotificationToast" onclick={() => this.attrs.state.dismiss(entry.id)}>
              {discussion && (
                <div className="NotificationToast-context">
                  <Link href={app.route.discussion(discussion)}>{discussion.title()}</Link>
                </div>
              )}
              <NotificationType notification={entry.notification} />
            </div>
          );
        })}
      </div>
    );
  }
}
