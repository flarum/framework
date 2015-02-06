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

		this.get('controller').on('loaded', this, this.loaded);
		this.get('controller').on('startWasChanged', this, this.startWasChanged);
	},

	willDestroyElement: function() {
		this.get('controller').off('loaded', this, this.loaded);
		this.get('controller').off('startWasChanged', this, this.startWasChanged);
	},

	// When the controller has finished loading, we want to scroll down to the
	// appropriate post instantly (without animation).
	loaded: function() {
		this.get('streamContent').send('goToNumber', this.get('controller.start'), true);
	},

	// When the start position of the discussion changes, we want to scroll
	// down to the appropriate post.
	startWasChanged: function(start) {
		this.get('streamContent').send('goToNumber', start);
	},

	// ------------------------------------------------------------------------
	// OBSERVERS
	// ------------------------------------------------------------------------

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
		var reply = ActionButton.create({
			label: 'Reply',
			icon: 'reply',
			action: function() {
				view.get('streamContent').send('goToLast');
				view.get('controller').send('reply');
			},
		});
		controls.pushObjectWithTag(reply, 'reply');
	}.on('populateControls')
});
