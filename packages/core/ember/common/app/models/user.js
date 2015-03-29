import DS from 'ember-data';

import HasItemLists from '../mixins/has-item-lists';
import stringToColor from '../utils/string-to-color';

export default DS.Model.extend(HasItemLists, {
  itemLists: ['badges'],

  username: DS.attr('string'),
  email: DS.attr('string'),
  password: DS.attr('string'),
  avatarUrl: DS.attr('string'),
  bio: DS.attr('string'),
  bioHtml: DS.attr('string'),
  preferences: DS.attr(),

  groups: DS.hasMany('group'),

  joinTime: DS.attr('date'),
  lastSeenTime: DS.attr('date'),
  online: Ember.computed('lastSeenTime', function() {
    return this.get('lastSeenTime') > moment().subtract(5, 'minutes').toDate();
  }),
  readTime: DS.attr('date'),
  unreadNotificationsCount: DS.attr('number'),

  discussionsCount: DS.attr('number'),
  commentsCount: DS.attr('number'),

  canEdit: DS.attr('boolean'),
  canDelete: DS.attr('boolean'),

  color: Ember.computed('username', function() {
    return '#'+stringToColor(this.get('username'));
  })
});
