System.register('flarum/flags/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export) {
  'use strict';

  var extend, app, PermissionGrid;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumComponentsPermissionGrid) {
      PermissionGrid = _flarumComponentsPermissionGrid['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-flags', function () {
        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('viewFlags', {
            icon: 'flag',
            label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
            permission: 'discussion.viewFlags'
          }, 65);
        });

        extend(PermissionGrid.prototype, 'replyItems', function (items) {
          items.add('flagPosts', {
            icon: 'flag',
            label: app.translator.trans('flarum-flags.admin.permissions.flag_posts_label'),
            permission: 'discussion.flagPosts'
          }, 70);
        });
      });
    }
  };
});