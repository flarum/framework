import Ember from 'ember';

// Represents a collection of results (e.g. a list of discussions)

export default Ember.Object.extend({
	
	// An array of the results.
	results: Em.A(),

	// The currently-active result.
	currentResult: null,

	sort: null,

	// The index of the currently-active result (determined by ID.) Returns '?'
	// if the currently-active result is not in the results list.
	index: function() {
		var index = '?';
		var id = this.get('currentResult.id');
		this.get('results').some(function(result, i) {
			if (result.get('id') == id) {
				index = i + 1;
				return true;
			}
		});
		return index;
	}.property('currentResult', 'results'),
	
	// The number of results.
	count: function() {
		return this.get('results.length');
	}.property('results'),

	// The previous result.
	previous: function() {
		return this.get('results').objectAt(this.get('index') - 2);
	}.property('index'),

	// The next result.
	next: function() {
		return this.get('results').objectAt(this.get('index'));
	}.property('index'),

});
