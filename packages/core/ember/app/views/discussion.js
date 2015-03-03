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
    items.pushObjectWithTag(DropdownSplit.extend({
      items: this.populateItemList('controls'),
      icon: 'reply',
      buttonClass: 'btn-primary',
      listItemClass: 'primary-control',
    }), 'controls');

    items.pushObjectWithTag(StreamScrubber.extend({
      streamContent: this.get('streamContent'),
      listItemClass: 'title-control'
    }), 'scrubber');
  },

  populateControls: function(items) {
    var view = this;

    this.addActionItem(items, 'reply', 'Reply', 'reply', null, function() {
      view.get('streamContent').send('goToLast');
      view.get('controller').send('reply');
    });

    this.addSeparatorItem(items);

    this.addActionItem(items, 'rename', 'Rename', 'pencil', 'discussion.canEdit', function() {
      var discussion = view.get('controller.model');
      var currentTitle = discussion.get('title');
      var title = prompt('Enter a new title for this discussion:', currentTitle);
      if (title && title !== currentTitle) {
        view.get('controller').send('rename', title);
      }
    });

    this.addActionItem(items, 'delete', 'Delete', 'times', 'discussion.canDelete', function() {
      if (confirm('Are you sure you want to delete this discussion?')) {
        view.get('controller').send('delete');
      }
    });
  }
});
