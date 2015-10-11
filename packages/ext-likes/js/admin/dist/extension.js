System.register('flarum/likes/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export) {
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

      app.initializers.add('flarum-likes', function () {
        extend(PermissionGrid.prototype, 'replyItems', function (items) {
          items.add('likePosts', {
            icon: 'thumbs-o-up',
            label: 'Like posts',
            permission: 'discussion.likePosts'
          });
        });
      });
    }
  };
});