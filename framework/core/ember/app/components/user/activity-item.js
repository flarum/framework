import Ember from 'ember';

import FadeIn from 'flarum/mixins/fade-in';

export default Ember.Component.extend(FadeIn, {
  layoutName: 'components/user/activity-item',
  tagName: 'li',

  componentName: Ember.computed('activity.type', function() {
    return 'user/activity-'+this.get('activity.type');
  })
});
