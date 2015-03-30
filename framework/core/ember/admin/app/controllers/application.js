import Ember from 'ember';

export default Ember.Controller.extend({
  actions: {
    toggleDrawer: function() {
      this.toggleProperty('drawerShowing');
    }
  }
});
