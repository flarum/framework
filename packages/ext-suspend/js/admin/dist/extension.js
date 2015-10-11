System.register('flarum/suspend/main', ['flarum/extend', 'flarum/app', 'flarum/components/PermissionGrid'], function (_export) {
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

      app.initializers.add('suspend', function () {
        extend(PermissionGrid.prototype, 'moderateItems', function (items) {
          items.add('suspendUsers', {
            icon: 'ban',
            label: 'Suspend users',
            permission: 'user.suspend'
          });
        });
      });
    }
  };
});