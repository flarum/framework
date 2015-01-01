import Ember from 'ember';

import Menu from '../utils/menu';
import MenuItem from '../components/menu-item';

export default Ember.View.extend({
	tagName: 'article',
	templateName: 'discussion-post',

	controls: null,

	contentComponent: function() {
		return 'post-type-'+this.get('post.type');
	}.property('post.type'),

	classNames: ['post'],
	classNameBindings: ['post.deleted', 'post.edited'],

	construct: function() {
		this.set('controls', Menu.create());

		var post = this.get('post');

		if (post.get('deleted')) {
			this.addControl('restore', 'Restore', 'reply', 'canEdit');
			this.addControl('delete', 'Delete', 'times', 'canDelete');
		} else {
			if (post.get('type') == 'comment') {
				this.addControl('edit', 'Edit', 'pencil', 'canEdit');
				this.addControl('hide', 'Delete', 'times', 'canEdit');
			} else {
				this.addControl('delete', 'Delete', 'times', 'canDelete');
			}
		}
	}.on('init'),

	didInsertElement: function() {
		this.$().hide().fadeIn('slow');
	},

	addControl: function(tag, title, icon, permissionAttribute) {
		if (permissionAttribute && ! this.get('post').get(permissionAttribute)) {
			return;
		}

		var self = this;
		var action = function(post) {
			self.get('controller').send(actionName, post);
		};

		var item = MenuItem.extend({title: title, icon: icon, action: action});
		this.get('controls').addItem(tag, item);
	}

});
