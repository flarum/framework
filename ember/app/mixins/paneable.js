import Ember from 'ember';

// This mixin defines a "paneable" controller - this is, one that has a
// portion of its interface that can be turned into a pane which slides out
// from the side of the screen. This is useful, for instance, when you have
// nested routes (index > discussion) and want to have the parent
// route's interface transform into a side pane when entering the child route.
export default Ember.Mixin.create({
	needs: ['application'],

	// Whether or not the "paneable" interface element is paned.
	paned: false,

	// Whether or not the pane should be visible on screen.
	paneShowing: false,
	paneHideTimeout: null,

	// Whether or not the pane is always visible on screen, even when the
	// mouse is taken away.
	panePinned: false,

	actions: {
		showPane: function() {
			if (this.get('paned')) {
				clearTimeout(this.get('paneHideTimeout'));
				this.set('paneShowing', true);
			}
		},

		hidePane: function(delay) {
			var controller = this;
			controller.set('paneHideTimeout', setTimeout(function() {
            	controller.set('paneShowing', false);
            }, delay || 250));
		},
		
		togglePinned: function() {
			this.toggleProperty('panePinned');
		}
	},

	// Tell the application controller when we pin/unpin the pane so that
	// other parts of the interface can respond appropriately.
	panePinnedChanged: function() {
		this.set('controllers.application.panePinned', this.get('paned') && this.get('panePinned'));
	}.observes('paned', 'panePinned')
});