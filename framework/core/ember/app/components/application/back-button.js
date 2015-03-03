import Ember from 'ember';

/**
  The back/pin button group in the top-left corner of Flarum's interface.
 */
export default Ember.Component.extend({
  classNames: ['back-button'],
  classNameBindings: ['active', 'className'],

  active: Ember.computed.or('target.paneIsShowing', 'target.paneIsPinned'),

  mouseEnter: function() {
    this.get('target').send('showPane');
  },

  mouseLeave: function() {
    this.get('target').send('hidePane');
  },

  actions: {
    back: function() {
      this.get('target').send('transitionFromBackButton');
      this.set('target', null);
    },

    togglePinned: function() {
      this.get('target').send('togglePinned');
    },

    toggleDrawer: function() {
      this.sendAction('toggleDrawer');
    }
  }
});
