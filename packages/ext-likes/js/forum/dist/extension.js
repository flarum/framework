System.register('flarum/likes/addLikeAction', ['flarum/extend', 'flarum/app', 'flarum/components/Button', 'flarum/components/CommentPost'], function (_export) {
  'use strict';

  var extend, app, Button, CommentPost;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }],
    execute: function () {
      _export('default', function () {
        extend(CommentPost.prototype, 'actionItems', function (items) {
          var post = this.props.post;

          if (post.isHidden() || !post.canLike()) return;

          var isLiked = app.session.user && post.likes().some(function (user) {
            return user === app.session.user;
          });

          items.add('like', Button.component({
            children: app.trans(isLiked ? 'flarum-likes.forum.unlike_action' : 'flarum-likes.forum.like_action'),
            className: 'Button Button--link',
            onclick: function onclick() {
              isLiked = !isLiked;

              post.save({ isLiked: isLiked });

              // We've saved the fact that we do or don't like the post, but in order
              // to provide instantaneous feedback to the user, we'll need to add or
              // remove the like from the relationship data manually.
              var data = post.data.relationships.likes.data;
              data.some(function (like, i) {
                if (like.id === app.session.user.id()) {
                  data.splice(i, 1);
                  return true;
                }
              });

              if (isLiked) {
                data.unshift({ type: 'users', id: app.session.user.id() });
              }
            }
          }));
        });
      });
    }
  };
});;System.register('flarum/likes/addLikesList', ['flarum/extend', 'flarum/app', 'flarum/components/CommentPost', 'flarum/helpers/punctuateSeries', 'flarum/helpers/username', 'flarum/helpers/icon', 'flarum/likes/components/PostLikesModal'], function (_export) {
  'use strict';

  var extend, app, CommentPost, punctuateSeries, username, icon, PostLikesModal;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }, function (_flarumHelpersPunctuateSeries) {
      punctuateSeries = _flarumHelpersPunctuateSeries['default'];
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername['default'];
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon['default'];
    }, function (_flarumLikesComponentsPostLikesModal) {
      PostLikesModal = _flarumLikesComponentsPostLikesModal['default'];
    }],
    execute: function () {
      _export('default', function () {
        extend(CommentPost.prototype, 'footerItems', function (items) {
          var post = this.props.post;
          var likes = post.likes();

          if (likes && likes.length) {
            var limit = 3;

            // Construct a list of names of users who have like this post. Make sure the
            // current user is first in the list, and cap a maximum of 3 names.
            var names = likes.sort(function (a) {
              return a === app.session.user ? -1 : 1;
            }).slice(0, limit).map(function (user) {
              return m(
                'a',
                { href: app.route.user(user), config: m.route },
                user === app.session.user ? app.trans('flarum-likes.forum.you') : username(user)
              );
            });

            // If there are more users that we've run out of room to display, add a "x
            // others" name to the end of the list. Clicking on it will display a modal
            // with a full list of names.
            if (likes.length > limit) {
              names.push(m(
                'a',
                { href: '#', onclick: function (e) {
                    e.preventDefault();
                    app.modal.show(new PostLikesModal({ post: post }));
                  } },
                app.trans('flarum-likes.forum.others', { count: likes.length - limit })
              ));
            }

            items.add('liked', m(
              'div',
              { className: 'Post-likedBy' },
              icon('thumbs-o-up'),
              app.trans('flarum-likes.forum.post_liked_by' + (likes[0] === app.session.user ? '_self' : ''), {
                count: names.length,
                users: punctuateSeries(names)
              })
            ));
          }
        });
      });
    }
  };
});;System.register('flarum/likes/main', ['flarum/extend', 'flarum/app', 'flarum/models/Post', 'flarum/Model', 'flarum/components/NotificationGrid', 'flarum/likes/addLikeAction', 'flarum/likes/addLikesList', 'flarum/likes/components/PostLikedNotification'], function (_export) {
  'use strict';

  var extend, app, Post, Model, NotificationGrid, addLikeAction, addLikesList, PostLikedNotification;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost['default'];
    }, function (_flarumModel) {
      Model = _flarumModel['default'];
    }, function (_flarumComponentsNotificationGrid) {
      NotificationGrid = _flarumComponentsNotificationGrid['default'];
    }, function (_flarumLikesAddLikeAction) {
      addLikeAction = _flarumLikesAddLikeAction['default'];
    }, function (_flarumLikesAddLikesList) {
      addLikesList = _flarumLikesAddLikesList['default'];
    }, function (_flarumLikesComponentsPostLikedNotification) {
      PostLikedNotification = _flarumLikesComponentsPostLikedNotification['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-likes', function () {
        app.notificationComponents.postLiked = PostLikedNotification;

        Post.prototype.canLike = Model.attribute('canLike');
        Post.prototype.likes = Model.hasMany('likes');

        addLikeAction();
        addLikesList();

        extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
          items.add('postLiked', {
            name: 'postLiked',
            icon: 'thumbs-o-up',
            label: app.trans('flarum-likes.forum.notify_post_liked')
          });
        });
      });
    }
  };
});;System.register('flarum/likes/components/PostLikedNotification', ['flarum/components/Notification', 'flarum/helpers/username', 'flarum/helpers/punctuate'], function (_export) {
  'use strict';

  var Notification, username, punctuate, PostLikedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification['default'];
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername['default'];
    }, function (_flarumHelpersPunctuate) {
      punctuate = _flarumHelpersPunctuate['default'];
    }],
    execute: function () {
      PostLikedNotification = (function (_Notification) {
        babelHelpers.inherits(PostLikedNotification, _Notification);

        function PostLikedNotification() {
          babelHelpers.classCallCheck(this, PostLikedNotification);
          babelHelpers.get(Object.getPrototypeOf(PostLikedNotification.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(PostLikedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'thumbs-o-up';
          }
        }, {
          key: 'href',
          value: function href() {
            return app.route.post(this.props.notification.subject());
          }
        }, {
          key: 'content',
          value: function content() {
            var notification = this.props.notification;
            var user = notification.sender();
            var auc = notification.additionalUnreadCount();

            return app.trans('flarum-likes.forum.post_liked_notification', {
              user: user,
              username: auc ? punctuate([username(user), app.trans('flarum-likes.forum.others', { count: auc })]) : undefined
            });
          }
        }, {
          key: 'excerpt',
          value: function excerpt() {
            return this.props.notification.subject().contentPlain();
          }
        }]);
        return PostLikedNotification;
      })(Notification);

      _export('default', PostLikedNotification);
    }
  };
});;System.register('flarum/likes/components/PostLikesModal', ['flarum/components/Modal', 'flarum/helpers/avatar', 'flarum/helpers/username'], function (_export) {
  'use strict';

  var Modal, avatar, username, PostLikesModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal['default'];
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar['default'];
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername['default'];
    }],
    execute: function () {
      PostLikesModal = (function (_Modal) {
        babelHelpers.inherits(PostLikesModal, _Modal);

        function PostLikesModal() {
          babelHelpers.classCallCheck(this, PostLikesModal);
          babelHelpers.get(Object.getPrototypeOf(PostLikesModal.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(PostLikesModal, [{
          key: 'className',
          value: function className() {
            return 'PostLikesModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.trans('likes.post_likes_modal_title');
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'ul',
                { className: 'PostLikesModal-list' },
                this.props.post.likes().map(function (user) {
                  return m(
                    'li',
                    null,
                    m(
                      'a',
                      { href: app.route.user(user), config: m.route },
                      avatar(user),
                      ' ',
                      ' ',
                      username(user)
                    )
                  );
                })
              )
            );
          }
        }]);
        return PostLikesModal;
      })(Modal);

      _export('default', PostLikesModal);
    }
  };
});