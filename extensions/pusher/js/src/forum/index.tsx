import * as PusherTypes from 'pusher-js';
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import type { Children } from 'mithril';
import type Tag from 'flarum/tags/common/models/Tag';

export type PusherBinding = {
  channels: {
    main: PusherTypes.Channel;
    user: PusherTypes.Channel | null;
  };
  pusher: PusherTypes.default;
};

app.initializers.add('flarum-pusher', () => {
  app.pusher = (async () => {
    // @ts-expect-error
    await import('//cdn.jsdelivr.net/npm/pusher-js@7.0.3/dist/web/pusher.min.js' /* webpackIgnore: true, webpackPrefetch: true */);

    // @ts-expect-error Imported dynamically
    const socket: PusherTypes.default = new Pusher(app.forum.attribute('pusherKey'), {
      authEndpoint: `${app.forum.attribute('apiUrl')}/pusher/auth`,
      cluster: app.forum.attribute('pusherCluster'),
      auth: {
        headers: {
          'X-CSRF-Token': app.session.csrfToken,
        },
      },
    });

    return {
      channels: {
        main: socket.subscribe('public'),
        user: app.session.user ? socket.subscribe(`private-user${app.session.user.id()}`) : null,
      },
      pusher: socket,
    };
  })();

  app.pushedUpdates = [];

  extend(DiscussionList.prototype, 'oncreate', function () {
    app.pusher.then((binding: PusherBinding) => {
      const pusher = binding.pusher;

      pusher.bind('newPost', (data: { tagIds: string[]; discussionId: number }) => {
        const params = app.discussions.getParams();

        if (!params.q && !params.sort && !params.filter) {
          if (params.tags) {
            const tag = app.store.getBy<Tag>('tags', 'slug', params.tags);
            const tagId = tag?.id();

            if (!tagId || !data.tagIds.includes(tagId)) return;
          }

          const id = String(data.discussionId);

          if ((!app.current.get('discussion') || id !== app.current.get('discussion').id()) && app.pushedUpdates.indexOf(id) === -1) {
            app.pushedUpdates.push(id);

            if (app.current.matches(IndexPage)) {
              app.setTitleCount(app.pushedUpdates.length);
            }

            m.redraw();
          }
        }
      });
    });
  });

  extend(DiscussionList.prototype, 'onremove', function () {
    app.pusher.then((binding: PusherBinding) => {
      binding.pusher.unbind('newPost');
    });
  });

  extend(DiscussionList.prototype, 'view', function (this: DiscussionList, vdom: Children) {
    if (app.pushedUpdates) {
      const count = app.pushedUpdates.length;

      if (count && typeof vdom === 'object' && vdom && 'children' in vdom && vdom.children instanceof Array) {
        vdom.children.unshift(
          <Button
            className="Button Button--block DiscussionList-update"
            onclick={() => {
              this.attrs.state.refresh().then(() => {
                this.loadingUpdated = false;
                app.pushedUpdates = [];
                app.setTitleCount(0);
                m.redraw();
              });
              this.loadingUpdated = true;
            }}
            loading={this.loadingUpdated}
          >
            {app.translator.trans('flarum-pusher.forum.discussion_list.show_updates_text', { count })}
          </Button>
        );
      }
    }
  });

  extend(DiscussionPage.prototype, 'oncreate', function (this: DiscussionPage) {
    app.pusher.then((binding: PusherBinding) => {
      const pusher = binding.pusher;

      pusher.bind('newPost', (data: { discussionId: number }) => {
        const id = String(data.discussionId);
        const discussionId = this.discussion?.id();

        if (this.discussion && discussionId === id && this.stream) {
          const oldCount = this.discussion.commentCount() ?? 0;

          app.store.find('discussions', discussionId).then(() => {
            this.stream?.update().then(m.redraw);

            if (!document.hasFocus()) {
              app.setTitleCount(Math.max(0, (this.discussion?.commentCount() ?? 0) - oldCount));

              window.addEventListener('focus', () => app.setTitleCount(0), { once: true });
            }
          });
        }
      });
    });
  });

  extend(DiscussionPage.prototype, 'onremove', function () {
    app.pusher.then((binding: PusherBinding) => {
      binding.pusher.unbind('newPost');
    });
  });

  extend(IndexPage.prototype, 'actionItems', (items: ItemList<Children>) => {
    items.remove('refresh');
  });

  app.pusher.then((binding: PusherBinding) => {
    const channels = binding.channels;

    if (channels.user) {
      channels.user.bind('notification', () => {
        if (app.session.user) {
          app.session.user.pushAttributes({
            unreadNotificationCount: app.session.user.unreadNotificationCount() ?? 0 + 1,
            newNotificationCount: app.session.user.newNotificationCount() ?? 0 + 1,
          });
        }
        app.notifications.clear();
        m.redraw();
      });
    }
  });
});
