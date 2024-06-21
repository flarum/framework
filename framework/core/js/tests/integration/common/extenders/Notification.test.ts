import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Extend from '../../../../src/common/extenders';
import NotificationType from '../../../../src/forum/components/NotificationType';
import NotificationComponent from '../../../../src/forum/components/Notification';
import Notification from '../../../../src/common/models/Notification';
import mq from 'mithril-query';
import app from '@flarum/core/src/forum/app';
import Mithril from 'mithril';

beforeAll(() => bootstrapForum());

describe('Notification extender', () => {
  app.store.pushPayload({
    data: {
      id: '1',
      type: 'discussions',
      attributes: {
        title: 'Discussion title',
      },
      relationships: {
        posts: {
          data: [
            { id: '1', type: 'posts' },
            { id: '2', type: 'posts' },
            { id: '3', type: 'posts' },
            { id: '4', type: 'posts' },
          ],
        },
      },
    },
  });

  const keanuNotification = new Notification({
    id: '1',
    type: 'notifications',
    attributes: { contentType: 'keanu', canEdit: false, createdAt: new Date() },
    relationships: { subject: { data: { type: 'discussions', id: '1' } } },
  });

  const normalNotification = new Notification({
    id: '2',
    type: 'notifications',
    attributes: { contentType: 'discussionRenamed', canEdit: false, createdAt: new Date(), content: { postNumber: 1 } },
    relationships: { subject: { data: { type: 'discussions', id: '1' } } },
  });

  it('existing notifications work by default', () => {
    app.boot();

    const notificationComponent = mq(NotificationType, {
      notification: normalNotification,
    });

    expect(notificationComponent).toContainRaw('changed the title');
  });

  it('does not work before registering the notification type', () => {
    app.boot();

    const notificationComponent = mq(NotificationType, {
      notification: keanuNotification,
    });

    expect(notificationComponent).not.toContainRaw('Keanu Reeves is awesome');
  });

  it('works after registering the notification type', () => {
    app.bootExtensions({
      test: {
        extend: [new Extend.Notification().add('keanu', KeanuNotification)],
      },
    });

    app.boot();

    const notificationComponent = mq(NotificationType, {
      notification: keanuNotification,
    });

    expect(notificationComponent).toContainRaw('Keanu Reeves is awesome');
  });
});

class KeanuNotification extends NotificationComponent {
  icon() {
    return 'fas fa-keanu';
  }

  content(): Mithril.Children {
    return 'Keanu Reeves is awesome';
  }

  excerpt(): Mithril.Children {
    return null;
  }

  href(): string {
    return '';
  }
}
