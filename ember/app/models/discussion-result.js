import Ember from 'ember';

var DiscussionResult = Ember.ObjectProxy.extend({

	relevantPosts: null,

	startPost: null,
	lastPost: null

});

export default DiscussionResult;
