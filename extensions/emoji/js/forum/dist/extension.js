System.register('flarum/emoji/main', ['flarum/extend', 'flarum/app', 'flarum/models/Post'], function (_export) {
  /*global twemoji, s9e*/

  'use strict';

  var override, app, Post;
  return {
    setters: [function (_flarumExtend) {
      override = _flarumExtend.override;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-emoji', function () {});
    }
  };
});