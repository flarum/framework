import Ember from 'ember';

/**
  A basic search input. Comes with the ability to be cleared by pressing
  escape or with a button. Sends an action when enter is pressed.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/search-input',
  classNames: ['search-input'],
  classNameBindings: ['active', 'value:clearable'],

  didInsertElement: function() {
    this.$('input').on('keydown', 'esc', function(e) {
      self.clear();
    });

    var self = this;
    this.$('.clear').on('mousedown click', function(e) {
      e.preventDefault();
    }).on('click', function(e) {
      self.clear();
    });
  },

  clear: function() {
    this.set('value', '');
    this.send('search');
    this.$().find('input').focus();
  },

  actions: {
    search: function() {
      this.get('action')(this.get('value'));
    }
  }
});
