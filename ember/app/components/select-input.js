import Ember from 'ember';

export default Ember.View.extend({

	tagName: 'span',
	classNames: ['select'],
	layout: Ember.Handlebars.compile('{{view Ember.Select content=view.content optionValuePath=view.optionValuePath optionLabelPath=view.optionLabelPath value=view.value}} {{fa-icon "sort"}}')

});
