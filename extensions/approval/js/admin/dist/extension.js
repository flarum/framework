System.register('flarum/approval/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export) {
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

      app.initializers.add('approval', function () {
        extend(PermissionGrid.prototype, 'replyItems', function (items) {
          items.add('replyWithoutApproval', {
            icon: 'check',
            label: 'Reply without approval',
            permission: 'discussion.replyWithoutApproval'
          }, 95);
        });

        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('approvePosts', {
            icon: 'check',
            label: 'Approve posts',
            permission: 'discussion.approvePosts'
          }, 65);
        });
      });
    }
  };
});