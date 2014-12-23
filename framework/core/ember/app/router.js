import Ember from 'ember';
import config from './config/environment';

console.log(config.locationType);
var Router = Ember.Router.extend({
  location: config.locationType
});

Router.map(function() {
	this.resource('categories', { path: '/categories' });

	this.resource('discussions', { path: '/' }, function() {
        this.resource('discussion', { path: '/:id/:slug' });
    });

	this.resource('user', { path: '/user/:username' }, function() {
		this.route('activity');
		this.route('posts');
		this.route('discussions');
		this.route('preferences');
	});
});

export default Router;
