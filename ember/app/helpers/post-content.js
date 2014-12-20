import Ember from 'ember';

// This helper takes a post as its argument and renders a certain component
// corresponding to the post's type. The naming convention is 'post-type-[type]'
// (for example, post-type-comment for a comment.) Other arguments added to the
// helper are passed through to the component.

export default Ember.Handlebars.makeBoundHelper(function(post, options) {
	options.hash.post = post;
	var component = 'post-type-'+post.get('type');
	var helper = Ember.Handlebars.resolveHelper(options.data.view.container, component);

	helper.call(this, options);
});
