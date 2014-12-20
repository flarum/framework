import Ember from 'ember';

var Router = Ember.Router.extend({
  location: FlarumENV.locationType
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
