import Ember from 'ember';

export default Ember.Component.extend({
  classNames: ['notification'],
  classNameBindings: ['notification.isRead::unread'],

  click: function() {
    console.log('click')
    this.get('notification').set('isRead', true).save();
  }
});
