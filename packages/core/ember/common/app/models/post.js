import Ember from 'ember';
import DS from 'ember-data';
import Subject from './subject';

export default Subject.extend({
  discussion: DS.belongsTo('discussion', {inverse: 'loadedPosts'}),
  number: DS.attr('number'),

  time: DS.attr('date'),
  user: DS.belongsTo('user'),
  contentType: DS.attr('string'),
  content: DS.attr(),
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
