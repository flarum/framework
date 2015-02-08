import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';
import ActionButton from '../ui/controls/action-button';
import ComposerEdit from '../discussions/composer-edit';
import AlertMessage from '../alert-message';
import humanTime from '../../utils/human-time';

var precompileTemplate = Ember.Handlebars.compile;

// @todo extend a base post class
export default Ember.Component.extend({
	tagName: 'article',
	layoutName: 'components/discussions/post-comment',

	editDescription: function() {
		return 'Edited by '+this.get('post.editUser.username')+' '+humanTime(this.get('post.editTime'));
	}.property('post.editTime', 'post.editUser'),

	post: Ember.computed.alias('content'),

	classNames: ['post'],
	classNameBindings: ['post.deleted', 'post.edited'],

	didInsertElement: function() {
		var $this = this.$();
		$this.css({opacity: 0});

		setTimeout(function() {
			$this.animate({opacity: 1}, 'fast');
		}, 100);

		this.set('controls', TaggedArray.create());
		this.trigger('populateControls', this.get('controls'));

		this.set('header', TaggedArray.create());
		this.trigger('populateHeader', this.get('header'));
	},

	populateControlsDefault: function(controls) {
		if (this.get('post.deleted')) {
			this.addControl('restore', 'Restore', 'reply', 'canEdit');
			this.addControl('delete', 'Delete', 'times', 'canDelete');
		} else {
			this.addControl('edit', 'Edit', 'pencil', 'canEdit');
			this.addControl('hide', 'Delete', 'times', 'canEdit');
		}
	}.on('populateControls'),

	populateHeaderDefault: function(header) {
		header.pushObjectWithTag(Ember.Component.create({
			tagName: 'h3',
			classNames: ['user'],
			layout: precompileTemplate('{{#link-to "user" post.user}}{{user-avatar post.user}} {{post.user.username}}{{/link-to}}'),
			post: this.get('post')
		}));

		header.pushObjectWithTag(Ember.Component.create({
			tagName: 'li',
			layout: precompileTemplate('{{#link-to "discussion" post.discussion (query-params start=post.number) class="time"}}{{human-time post.time}}{{/link-to}}'),
			post: this.get('post')
		}));

		header.pushObjectWithTag(Ember.Component.extend({
			tagName: 'li',
			hideItem: Ember.computed.not('parent.post.isEdited'),
			layout: precompileTemplate('<span class="post-edited" {{bind-attr title=parent.editDescription}}>{{fa-icon "pencil"}}</span>'),
			parent: this,
			didInsertElement: function() {
				this.$('.post-edited').tooltip();
			},
			updateTooltip: function() {
				Ember.run.scheduleOnce('afterRender', this, function() {
					this.$('.post-edited').tooltip('fixTitle');
				});
			}.observes('parent.editDescription')
		}).create());
	}.on('populateHeader'),

	addControl: function(tag, label, icon, permissionAttribute) {
		if (permissionAttribute && !this.get('post').get(permissionAttribute)) {
			return;
		}

		var self = this;
		var action = function() {
			self.get('controller').send(tag);
		};

		var item = ActionButton.create({label: label, icon: icon, action: action});
		this.get('controls').pushObjectWithTag(item, tag);
	},

	savePost: function(post, data) {
		var controller = this;
        var composer = this.get('composer');

        composer.set('content.loading', true);
        this.get('alerts').send('clearAlerts');

        post.set('content', data.content);

        return post.save().then(function(post) {
            composer.send('hide');
        },
        function(reason) {
            var errors = reason.errors;
            for (var i in reason.errors) {
                var message = AlertMessage.create({
                    type: 'warning',
                    message: reason.errors[i]
                });
                controller.get('alerts').send('alert', message);
            }
        })
        .finally(function() {
            composer.set('content.loading', false);
        });
	},

	actions: {
		renderControls: function() {
			this.set('renderControls', this.get('controls'));
			// if (!this.get('controls.length')) {
			// 	this.get('controls').pushObject(Ember.Component.create({tagName: 'li', classNames: ['dropdown-header'], layout: Ember.Handlebars.compile('No actions available')}));
			// }
		},

		edit: function() {
			var component = this;
            var post = this.get('post');
            var composer = this.get('composer');

            // If the composer is already set up for this post, then we
            // don't need to change its content - we can just show it.
            if (!(composer.get('content') instanceof ComposerEdit) || composer.get('content.post') !== post) {
                composer.switchContent(ComposerEdit.create({
                    user: post.get('user'),
                    post: post,
                    submit: function(data) {
                        component.savePost(post, data);
                    }
                }));
            }

            composer.send('show');
		}
	}
});
