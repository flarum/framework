System.register('flarum/akismet/main', ['flarum/extend', 'flarum/app', 'flarum/utils/PostControls', 'flarum/components/CommentPost'], function (_export) {
  'use strict';

  var extend, override, app, PostControls, CommentPost;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      override = _flarumExtend.override;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-akismet', function () {
        extend(PostControls, 'destructiveControls', function (items, post) {
          if (items.has('approve')) {
            var flags = post.flags();

            if (flags && flags.some(function (flag) {
              return flag.type() === 'akismet';
            })) {
              items.get('approve').props.children = 'Not Spam';
            }
          }
        });

        override(CommentPost.prototype, 'flagReason', function (original, flag) {
          if (flag.type() === 'akismet') {
            return 'Akismet flagged as Spam';
          }

          return original(flag);
        });
      }, -20); // run after the approval extension
    }
  };
});