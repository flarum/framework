import Ember from 'ember';
import config from './config/environment';

var Router = Ember.Router.extend({
  location: config.locationType
});

Router.map(function() {
  this.resource('index', {path: '/'}, function() {
    this.resource('discussion', {path: '/:id/:slug'}, function() {
      this.route('near', {path: '/:near'});
    });
  });

  this.resource('user', {path: '/u/:username'}, function() {
    this.route('activity', {path: '/'});
    this.route('discussions');
    this.route('posts');
    this.route('edit');
  });

  this.resource('settings');
});

export default Router;
