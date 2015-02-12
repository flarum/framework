import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import DropdownSplit from 'flarum/components/ui/dropdown-split';
import StreamScrubber from 'flarum/components/discussion/stream-scrubber';

var $ = Ember.$;

export default Ember.View.extend(HasItemLists, {
  itemLists: ['sidebar'],

  discussion: Ember.computed.alias('controller.model'),

  didInsertElement: function() {
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
  updateTitle: Ember.observer('controller.model.title', function() {
    this.set('controller.controllers.application.pageTitle', this.get('controller.model.title'));
  }),

  // ------------------------------------------------------------------------
  // LISTENERS
  // ------------------------------------------------------------------------

  populateSidebar: function(items) {
    items.pushObjectWithTag(DropdownSplit.create({
      items: this.populateItemList('controls'),
      icon: 'reply',
      buttonClass: 'btn-primary'
    }), 'controls');

    items.pushObjectWithTag(StreamScrubber.create({
      streamContent: this.get('streamContent')
    }), 'scrubber');
  },

  populateControls: function(items) {
    var view = this;

    this.addActionItem(items, 'reply', 'Reply', 'reply', 'discussion.canReply', function() {
      view.get('streamContent').send('goToLast');
      view.get('controller').send('reply');
    });
  }
});
