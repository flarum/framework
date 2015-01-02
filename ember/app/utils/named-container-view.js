import Ember from 'ember';

export default Ember.ArrayProxy.extend({

    content: null,

	namedViews: null,

    init: function() {
        this.set('content', Ember.A());
        this.set('namedViews', Ember.Object.create());
        this._super();
    },

	// Add an item to the container.
	addItem: function(name, viewClass, index) {
		// view = this.createChildView(view);

		if (typeof index == 'undefined') {
			index = this.get('length');
		}
		this.replace(index, 0, [viewClass]);
		this.get('namedViews').set(name, viewClass);
	},

	// Remove an item from the container.
	removeItem: function(name) {
		this.removeObject(this.get('namedViews').get(name));
		this.get('namedViews').set(name, null);
	},

	// Replace an item in the container with another one.
	replaceItem: function(name, viewClass) {
		// view = this.createChildView(view);

		var oldView = this.get('namedViews').get(name);
		var index = this.indexOf(oldView);
		this.replace(index, 1, [viewClass])
		this.get('namedViews').set(name, viewClass);
	},

	// Move an item in the container to a new position.
	moveItem: function(name, index) {
		var view = this.get('namedViews').get(name);
		this.removeItem(name);
		this.addItem(name, view, index);
	},

	firstItem: function() {
    	return this.objectAt(0);
    }.property('content.@each'),

    getItem: function(name) {
    	return this.get('namedViews').get(name);
    }

});
