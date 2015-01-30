import Ember from 'ember';
import DS from 'ember-data';

export default DS.Model.extend({

	discussion: DS.belongsTo('discussion', {inverse: null}),
	number: DS.attr('number'),

	time: DS.attr('string'),
	user: DS.belongsTo('user'),
	type: DS.attr('string'),
	content: DS.attr('string'),
	contentHtml: DS.attr('string'),

	editTime: DS.attr('string'),
	editUser: DS.belongsTo('user'),
	edited: Ember.computed.notEmpty('editTime'),

	deleteTime: DS.attr('string'),
	deleteUser: DS.belongsTo('user'),
	deleted: Ember.computed.notEmpty('deleteTime'),

	canEdit: DS.attr('boolean'),
	canDelete: DS.attr('boolean')

});
