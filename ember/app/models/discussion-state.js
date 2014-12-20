import Ember from 'ember';
import DS from 'ember-data';

export default DS.Model.extend({
	readTime: DS.attr('date'),
	readNumber: DS.attr('number')
});
