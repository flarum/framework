import Ember from 'ember';

var DiscussionResult = Ember.ObjectProxy.extend({

	relevantPosts: Em.A(),

	startPost: null,
	lastPost: null

});

export default DiscussionResult;
