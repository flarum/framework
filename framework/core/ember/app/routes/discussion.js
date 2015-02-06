import Ember from 'ember';

import PostStream from '../models/post-stream';

export default Ember.Route.extend({
	queryParams: {
		start: {replace: true}
	},

	model: function(params) {
		return this.store.findQueryOne('discussion', params.id, {
			include: 'posts',
			near: params.start
		});
	},

	resetController: function(controller) {
		// Whenever we exit the discussion view, or transition to a different
		// discussion, we want to reset the query params so that they don't stick.
		controller.set('start', '1');
		controller.set('searchQuery', '');
		controller.set('loaded', false);
		controller.set('stream', null);
	},

	setupController: function(controller, discussion) {
		controller.set('model', discussion);
		this.controllerFor('index/index').set('lastDiscussion', discussion);

        // Set up the post stream object. It needs to know about the discussion
        // it's representing the posts for, and we also need to inject the Ember
        // Data store.
        var stream = PostStream.create({
        	discussion: discussion,
        	store: this.store
        });
        controller.set('stream', stream);

        // Next, we need to make sure we have a list of the discussion's post
        // IDs. If we don't already have this information, we'll need to
        // reload the discussion model.
        var promise = discussion.get('posts') ? Ember.RSVP.resolve(discussion) : this.model({
        	id: discussion.get('id'),
        	start: controller.get('start')
        });

        // Each time we view a discussion we want to reload its posts from
		// scratch so that we have the most up-to-date data. Also, if we were
		// to leave them in the store, the stream would try and render them
		// which has the potential to be slow.
		this.store.unloadAll('post');

        // When we know we have the post IDs, we can set up the post stream with
        // them. Then we will tell the view that we have finished loading so that
        // it can scroll down to the appropriate post.
        promise.then(function(discussion) {
            stream.setup(discussion.get('postIds'));
            controller.store.push('discussion', {id: discussion.get('id'), posts: ''});
            if (controller.get('model') === discussion) {
	            controller.set('loaded', true);
	            Ember.run.scheduleOnce('afterRender', function() {
	            	controller.trigger('loaded');
	            });
	        }
        });
	},

	actions: {
		queryParamsDidChange: function(params) {
			// If the ?start param has changed, we want to tell the view to
			// tell the streamContent component to jump to this start point.
			// We postpone running this code until the next run loop because
			// when transitioning directly from one discussion to another,
			// queryParamsDidChange is fired before the controller is reset.
			// Thus, controller.loaded would still be true and the
			// startWasChanged event would be triggered inappropriately.
			var newStart = parseInt(params.start) || 1;
			var controller = this.controllerFor('discussion');
			var oldStart = parseInt(controller.get('start'));
			Ember.run.next(function() {
				if (controller.get('loaded') && newStart !== oldStart) {
					controller.trigger('startWasChanged', newStart);
				}
			});
		},

		didTransition: function() {
			// When we transition into a new discussion, we want to hide the
			// discussions list pane. This means that when the user selects a
			// different discussion within the pane, the pane will slide away.
			// We also minimize the composer.
			this.controllerFor('index')
				.set('paned', true)
				.set('paneShowing', false);
			this.controllerFor('composer').send('minimize');
		},
	}
});
