import DS from 'ember-data';

import HasItemLists from 'flarum/mixins/has-item-lists';
import stringToColor from 'flarum/utils/string-to-color';

export default DS.Model.extend(HasItemLists, {
  itemLists: ['badges'],

  username: DS.attr('string'),
  email: DS.attr('string'),
  password: DS.attr('string'),
  avatarUrl: DS.attr('string'),

  groups: DS.hasMany('group'),

  joinTime: DS.attr('date'),
  lastSeenTime: DS.attr('date'),
  readTime: DS.attr('date'),

  discussionsCount: DS.attr('number'),
  postsCount: DS.attr('number'),

  canEdit: DS.attr('boolean'),
  canDelete: DS.attr('boolean'),

  color: Ember.computed('username', function() {
    return '#'+stringToColor(this.get('username'));
  })
});
