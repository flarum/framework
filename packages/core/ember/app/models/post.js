import Ember from 'ember';
import DS from 'ember-data';

export default DS.Model.extend({
  discussion: DS.belongsTo('discussion', {inverse: 'loadedPosts'}),
  number: DS.attr('number'),

  time: DS.attr('date'),
  user: DS.belongsTo('user'),
  type: DS.attr('string'),
  content: DS.attr('string'),
  contentHtml: DS.attr('string'),

  editTime: DS.attr('date'),
  editUser: DS.belongsTo('user'),
  isEdited: Ember.computed.notEmpty('editTime'),

  hideTime: DS.attr('date'),
  hideUser: DS.belongsTo('user'),
  isHidden: DS.attr('boolean'),

  canEdit: DS.attr('boolean'),
  canDelete: DS.attr('boolean')
});
