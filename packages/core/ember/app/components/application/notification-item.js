import Ember from 'ember';

import FadeIn from 'flarum/mixins/fade-in';

export default Ember.Component.extend(FadeIn, {
  layoutName: 'components/application/notification-item',
  tagName: 'li',

  componentName: Ember.computed('notification.contentType', function() {
    return 'application/notification-'+this.get('notification.contentType');
  })
});
