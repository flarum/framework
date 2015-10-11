System.register('flarum/sticky/addStickyBadge', ['flarum/extend', 'flarum/models/Discussion', 'flarum/components/Badge'], function (_export) {
  'use strict';

  var extend, Discussion, Badge;

  _export('default', addStickyBadge);

  function addStickyBadge() {
    extend(Discussion.prototype, 'badges', function (badges) {
      if (this.isSticky()) {
        badges.add('sticky', Badge.component({
          type: 'sticky',
          label: app.trans('flarum-sticky.forum.stickied'),
          icon: 'thumb-tack'
        }), 10);
      }
    });
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion['default'];
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge['default'];
    }],
    execute: function () {}
  };
});;System.register('flarum/sticky/addStickyControl', ['flarum/extend', 'flarum/utils/DiscussionControls', 'flarum/components/DiscussionPage', 'flarum/components/Button'], function (_export) {
  'use strict';

  var extend, DiscussionControls, DiscussionPage, Button;

  _export('default', addStickyControl);

  function addStickyControl() {
    extend(DiscussionControls, 'moderationControls', function (items, discussion) {
      if (discussion.canSticky()) {
        items.add('sticky', Button.component({
          children: app.trans(discussion.isSticky() ? 'flarum-sticky.forum.unsticky' : 'flarum-sticky.forum.sticky'),
          icon: 'thumb-tack',
          onclick: this.stickyAction.bind(discussion)
        }));
      }
    });

    DiscussionControls.stickyAction = function () {
      this.save({ isSticky: !this.isSticky() }).then(function () {
        if (app.current instanceof DiscussionPage) {
          app.current.stream.update();
        }

        m.redraw();
      });
    };
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls['default'];
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage['default'];
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }],
    execute: function () {}
  };
});;System.register('flarum/sticky/addStickyExcerpt', ['flarum/extend', 'flarum/components/DiscussionList', 'flarum/components/DiscussionListItem', 'flarum/utils/string'], function (_export) {
  'use strict';

  var extend, DiscussionList, DiscussionListItem, truncate;

  _export('default', addStickyControl);

  function addStickyControl() {
    extend(DiscussionList.prototype, 'requestParams', function (params) {
      params.include.push('startPost');
    });

    extend(DiscussionListItem.prototype, 'infoItems', function (items) {
      var discussion = this.props.discussion;

      if (discussion.isSticky()) {
        var startPost = discussion.startPost();

        if (startPost) {
          var excerpt = m(
            'span',
            null,
            truncate(startPost.contentPlain(), 200)
          );

          items.add('excerpt', excerpt, -100);
        }
      }
    });
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList['default'];
    }, function (_flarumComponentsDiscussionListItem) {
      DiscussionListItem = _flarumComponentsDiscussionListItem['default'];
    }, function (_flarumUtilsString) {
      truncate = _flarumUtilsString.truncate;
    }],
    execute: function () {}
  };
});;System.register('flarum/sticky/main', ['flarum/extend', 'flarum/app', 'flarum/Model', 'flarum/models/Discussion', 'flarum/sticky/components/DiscussionStickiedPost', 'flarum/sticky/addStickyBadge', 'flarum/sticky/addStickyControl', 'flarum/sticky/addStickyExcerpt'], function (_export) {
  'use strict';

  var extend, notificationType, app, Model, Discussion, DiscussionStickiedPost, addStickyBadge, addStickyControl, addStickyExcerpt;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      notificationType = _flarumExtend.notificationType;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumModel) {
      Model = _flarumModel['default'];
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion['default'];
    }, function (_flarumStickyComponentsDiscussionStickiedPost) {
      DiscussionStickiedPost = _flarumStickyComponentsDiscussionStickiedPost['default'];
    }, function (_flarumStickyAddStickyBadge) {
      addStickyBadge = _flarumStickyAddStickyBadge['default'];
    }, function (_flarumStickyAddStickyControl) {
      addStickyControl = _flarumStickyAddStickyControl['default'];
    }, function (_flarumStickyAddStickyExcerpt) {
      addStickyExcerpt = _flarumStickyAddStickyExcerpt['default'];
    }],
    execute: function () {

      app.postComponents.discussionStickied = DiscussionStickiedPost;

      Discussion.prototype.isSticky = Model.attribute('isSticky');
      Discussion.prototype.canSticky = Model.attribute('canSticky');

      addStickyBadge();
      addStickyControl();
      addStickyExcerpt();
    }
  };
});;System.register('flarum/sticky/components/DiscussionStickiedNotification', ['flarum/components/Notification'], function (_export) {
  'use strict';

  var Notification, DiscussionStickiedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification['default'];
    }],
    execute: function () {
      DiscussionStickiedNotification = (function (_Notification) {
        babelHelpers.inherits(DiscussionStickiedNotification, _Notification);

        function DiscussionStickiedNotification() {
          babelHelpers.classCallCheck(this, DiscussionStickiedNotification);
          babelHelpers.get(Object.getPrototypeOf(DiscussionStickiedNotification.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(DiscussionStickiedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'thumb-tack';
          }
        }, {
          key: 'href',
          value: function href() {
            var notification = this.props.notification;

            return app.route.discussion(notification.subject(), notification.content().postNumber);
          }
        }, {
          key: 'content',
          value: function content() {
            return app.trans('flarum-sticky.forum.discussion_stickied_notification', { user: this.props.notification.sender() });
          }
        }]);
        return DiscussionStickiedNotification;
      })(Notification);

      _export('default', DiscussionStickiedNotification);
    }
  };
});;System.register('flarum/sticky/components/DiscussionStickiedPost', ['flarum/components/EventPost'], function (_export) {
  'use strict';

  var EventPost, DiscussionStickiedPost;
  return {
    setters: [function (_flarumComponentsEventPost) {
      EventPost = _flarumComponentsEventPost['default'];
    }],
    execute: function () {
      DiscussionStickiedPost = (function (_EventPost) {
        babelHelpers.inherits(DiscussionStickiedPost, _EventPost);

        function DiscussionStickiedPost() {
          babelHelpers.classCallCheck(this, DiscussionStickiedPost);
          babelHelpers.get(Object.getPrototypeOf(DiscussionStickiedPost.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(DiscussionStickiedPost, [{
          key: 'icon',
          value: function icon() {
            return 'thumb-tack';
          }
        }, {
          key: 'descriptionKey',
          value: function descriptionKey() {
            return this.props.post.content().sticky ? 'flarum-sticky.forum.discussion_stickied_post' : 'flarum-sticky.forum.discussion_unstickied_post';
          }
        }]);
        return DiscussionStickiedPost;
      })(EventPost);

      _export('default', DiscussionStickiedPost);
    }
  };
});