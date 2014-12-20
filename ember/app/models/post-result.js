import Ember from 'ember';

var PostResult = Ember.ObjectProxy.extend({

	relevantContent: ''

});

PostResult.reopenClass({
	create: function(post) {
		if (!post) return null;
		
		var result = this._super();
		result.set('content', post);
		result.set('relevantContent', post.get('content'));
		return result;
	}
});

export default PostResult;
