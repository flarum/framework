import Ember from 'ember';

export default Ember.Route.extend({

	queryParams: {
		start: {replace: true}
	},

	model: function(params) {
		return this.store.find('discussion', params.id);
	},

	resetController: function(controller) {
		// Whenever we exit the discussion view, or transition to a different
		// discussion, we want to reset the query params so that they don't stick.
		controller.set('start', '1');
		controller.set('searchQuery', '');
		controller.set('loaded', false);
		controller.set('postStream', null);
	},

	setupController: function(controller, model) {
		controller.setup(model);

		this.controllerFor('application').set('showDiscussionStream', true);
		this.controllerFor('discussions').set('paned', true);
		this.controllerFor('discussions').set('current', model);
	},

	actions: {

		queryParamsDidChange: function(params) {
			// We're only interested in changes to the ?start param, and we're
			// not interested if nothing has actually changed. If the start
			// param has changed, we want to tell the controller to load posts
			// near it.
			if (! params.start || params.start == this.get('controller.start') || ! this.get('controller.loaded')) {
				return;
			}
			this.get('controller').send('jumpToNumber', params.start);
		},

		willTransition: function(transition) {
			// If we're going to transition out, we need to abort any unfinished
			// AJAX requests. We need to do this because sometimes a transition
			// to another discussion will happen very rapidly (i.e. when using
			// the arrow buttons on the result stream.) If a previous
			// discussion's posts finish loading while displaying a new
			// discussion, strange things will happen.
			this.store.adapterFor('discussion').xhr.forEach(function(xhr) {
				xhr.abort();
			});
		}

	}
});
