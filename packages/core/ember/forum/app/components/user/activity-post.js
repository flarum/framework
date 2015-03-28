import Ember from 'ember';

export default Ember.Component.extend({
  layoutName: 'components/user/activity-post',

  isFirstPost: Ember.computed('activity.post.number', function() {
    return this.get('activity.post.number') === 1;
  })
});
