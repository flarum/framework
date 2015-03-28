import Ember from 'ember';

/**
  The back/pin button group in the top-left corner of Flarum's interface.
 */
export default Ember.Component.extend({
  classNames: ['back-button'],
  classNameBindings: ['active', 'className'],

  active: Ember.computed.or('target.paneIsShowing', 'target.paneIsPinned'),

  mouseEnter: function() {
    var target = this.get('target');
    if (target) {
      target.send('showPane');
    }
  },

  mouseLeave: function() {
    var target = this.get('target');
    if (target) {
      target.send('hidePane');
    }
  },

  actions: {
    // WE HAVE TO GO BACK. WAAAAAALLLLLLTTTTT
    back: function() {
      this.sendAction('goBack');
    },

    togglePinned: function() {
      var target = this.get('target');
      if (target) {
        target.send('togglePinned');
      }
    },

    toggleDrawer: function() {
      this.sendAction('toggleDrawer');
    }
  }
});
