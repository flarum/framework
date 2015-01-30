import Ember from 'ember';

export default Ember.Controller.extend({

    needs: ['index', 'application'],

    user: Ember.Object.create({avatarNumber: 1}),

    discussion: null,

    showing: true,
    minimized: false,

    title: 'Replying to <em>Some Discussion Title</em>',

    actions: {
        close: function() {
            this.set('showing', false);
        },
        minimize: function() {
        	this.set('minimized', true);
        },
        show: function() {
        	this.set('minimized', false);
        },
        save: function(value) {
        	var store = this.store;
        	var discussion = this.get('discussion');
        	var controller = this;

        	var post = store.createRecord('post', {
			  content: value,
			  discussion: discussion
			});
        	post.save().then(function(post) {
        		discussion.set('posts', discussion.get('posts')+','+post.get('id'));
        		controller.get('delegate').send('replyAdded', post);
        	});
        }
    }

});
