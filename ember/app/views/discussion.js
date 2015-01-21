import Ember from 'ember';

import TaggedArray from '../utils/tagged-array';
import ActionButton from '../components/ui/controls/action-button';
import DropdownSplit from '../components/ui/controls/dropdown-split';
import StreamScrubber from '../components/discussions/stream-scrubber';

var $ = Ember.$;

export default Ember.View.extend(Ember.Evented, {

	sidebarItems: null,

	didInsertElement: function() {
		// Create and populate an array of items to be rendered in the sidebar.
		var sidebarItems = TaggedArray.create();
		this.trigger('populateSidebar', sidebarItems);
		this.set('sidebarItems', sidebarItems);

		// By this stage the discussion controller has initialized the post
		// stream object, and there may or may not be posts loaded into it.
		// Either way, we want to tell our stream content component to jump
		// down to the start position specified in the controller's query
		// params.
		this.loadStreamContentForNewDiscussion();

		// For that matter, whenever the controller's start query param
		// changes, we want to tell our stream content component to jump down
		// to it.
		this.get('controller').on('startWasChanged', this, this.goToNumber);
	},

	willDestroyElement: function() {
		this.get('controller').off('startWasChanged', this, this.goToNumber);
	},

	goToNumber: function(start) {
		// We can only proceed if the controller has loaded the discussion
		// details and the view has been rendered.
		if (this.get('controller.loaded') && this.get('streamContent')) {
			this.get('streamContent').send('goToNumber', start);
		}
	},

	// ------------------------------------------------------------------------
	// OBSERVERS
	// ------------------------------------------------------------------------

	// Whenever the controller has switched out the old discussion model for a
	// new one, we want to 
	loadStreamContentForNewDiscussion: function() {
		if (this.get('controller.loaded')) {
			this.goToNumber(this.get('controller.start'));
		}
	}.observes('controller.loaded'),

	// Whenever the model's title changes, we want to update that document's
	// title the reflect the new title.
	updateTitle: function() {
		this.set('controller.controllers.application.pageTitle', this.get('controller.model.title'));
	}.observes('controller.model.title'),

	// ------------------------------------------------------------------------
	// LISTENERS
	// ------------------------------------------------------------------------

	populateSidebarDefault: function(sidebar) {
		var controls = TaggedArray.create();
		this.trigger('populateControls', controls);
		sidebar.pushObjectWithTag(DropdownSplit.create({
			items: controls,
			icon: 'reply',
			buttonClass: 'btn-primary'
		}), 'controls');

		sidebar.pushObjectWithTag(StreamScrubber.create({
			streamContent: this.get('streamContent')
		}), 'scrubber');
	}.on('populateSidebar'),

	populateControlsDefault: function(controls) {
		var view = this;
		var ReplyItem = ActionButton.extend({
			label: 'Reply',
			icon: 'reply',
			classNameBindings: ['className', 'replying:disabled'],
			replying: function() {
				return this.get('parentController.controllers.composer.showing');
			}.property('parentController.controllers.composer.showing'),
			action: function() {
				var lastPost = $('.posts .item:last');
				$('html, body').animate({scrollTop: lastPost.offset().top + lastPost.outerHeight() - $(window).height() + $('.composer').height() + 19}, 'fast');
				view.get('controller').send('reply');
			},
			parentController: this.get('controller'),
		});
		controls.pushObjectWithTag(ReplyItem.create(), 'reply');
	}.on('populateControls')
});
