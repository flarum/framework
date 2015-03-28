import Ember from 'ember';

/**
  Component for the "welcome to this forum" hero on the discussions index.
 */
export default Ember.Component.extend({
  layoutName: 'components/index/welcome-hero',
  tagName: 'header',
  classNames: ['hero', 'welcome-hero'],

  title: '',
  description: '',

  actions: {
    close: function() {
      this.$().slideUp();
    }
  }
});
