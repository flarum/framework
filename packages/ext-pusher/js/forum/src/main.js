/*global Pusher*/

import { extend } from 'flarum/extend';
import app from 'flarum/app';
import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionPage from 'flarum/components/DiscussionPage';
import IndexPage from 'flarum/components/IndexPage';
import Button from 'flarum/components/Button';

app.initializers.add('pusher', () => {
  const loadPusher = m.deferred();

  $.getScript('//js.pusher.com/3.0/pusher.min.js', () => {
    const socket = new Pusher(app.forum.attribute('pusherKey'), {
      authEndpoint: app.forum.attribute('apiUrl') + '/pusher/auth',
      auth: {
        headers: {
          'Authorization': 'Token ' + app.session.token
        }
      }
    });

    loadPusher.resolve({
      main: socket.subscribe('public'),
      user: app.session.user ? socket.subscribe('private-user' + app.session.user.id()) : null
    });
  });

  app.pusher = loadPusher.promise;
  app.pushedUpdates = [];

  extend(DiscussionList.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channels => {
      channels.main.bind('newPost', data => {
        const params = this.props.params;

        if (!params.q && !params.sort) {
          if (params.tags) {
            const tag = app.store.getBy('tags', 'slug', params.tags);

            if (data.tagIds.indexOf(tag.id()) === -1) return;
          }

          if ((!app.current.discussion || data.discussionId !== app.current.discussion.id()) && app.pushedUpdates.indexOf(data.discussionId) === -1) {
            app.pushedUpdates.push(data.discussionId);

            if (app.current instanceof IndexPage) {
              app.setTitleCount(app.pushedUpdates.length);
            }

            m.redraw();
          }
        }
      });

      extend(context, 'onunload', () => channels.main.unbind());
    });
  });

  extend(DiscussionList.prototype, 'view', function(vdom) {
    if (app.pushedUpdates) {
      const count = app.pushedUpdates.length;

      if (count) {
        vdom.children.unshift(
          Button.component({
            className: 'Button Button--block DiscussionList-update',
            onclick: () => {
              this.refresh(false).then(() => {
                this.loadingUpdated = false;
                app.pushedUpdates = [];
                app.setTitleCount(0);
                m.redraw();
              });
              this.loadingUpdated = true;
            },
            loading: this.loadingUpdated,
            children: app.trans('pusher.show_updated_discussions', {count})
          })
        );
      }
    }
  });

  extend(DiscussionPage.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channels => {
      channels.main.bind('newPost', data => {
        if (this.discussion && this.discussion.id() === data.discussionId && this.stream) {
          const oldCount = this.discussion.commentsCount();

          app.store.find('discussions', this.discussion.id()).then(() => {
            this.stream.update();

            if (!document.hasFocus()) {
              app.setTitleCount(Math.max(0, this.discussion.commentsCount() - oldCount));

              $(window).one('focus', () => app.setTitleCount(0));
            }
          });
        }
      });

      extend(context, 'onunload', () => channels.main.unbind());
    });
  });

  extend(IndexPage.prototype, 'actionItems', items => {
    delete items.refresh;
  });

  app.pusher.then(channels => {
    if (channels.user) {
      channels.user.bind('notification', () => {
        app.session.user.pushAttributes({
          unreadNotificationsCount: app.session.user.unreadNotificationsCount() + 1
        });
        delete app.cache.notifications;
        m.redraw();
      });
    }
  });
});
