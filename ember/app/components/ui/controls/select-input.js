import Ember from 'ember';

export default Ember.View.extend({
	tagName: 'span',
	classNames: ['select-input'],
	optionValuePath: 'content',
	optionLabelPath: 'content',
	layout: Ember.Handlebars.compile('{{view "select" content=view.content optionValuePath=view.optionValuePath optionLabelPath=view.optionLabelPath value=view.value class="form-control"}} {{fa-icon "sort"}}')
});
