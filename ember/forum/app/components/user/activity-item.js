import Ember from 'ember';

import FadeIn from 'flarum-forum/mixins/fade-in';

export default Ember.Component.extend(FadeIn, {
  layoutName: 'components/user/activity-item',
  tagName: 'li',

  componentName: Ember.computed('activity.contentType', function() {
    return 'user/activity-'+this.get('activity.contentType');
  })
});
