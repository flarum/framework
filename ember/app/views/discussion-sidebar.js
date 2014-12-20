import Ember from 'ember';

import NamedContainerView from '../utils/named-container-view';
import Menu from '../utils/menu';
import MenuSplit from '../components/menu-split';
import MenuItem from '../components/menu-item';
import DiscussionScrollbar from './discussion-scrollbar';

export default Ember.View.extend({

	// NamedContainerView which will be rendered in the template.
	content: null,
	controls: null,

    template: Ember.Handlebars.compile('{{menu-list items=view.toolbar class="toolbar"}}{{menu-list items=view.content class="body"}}'),

	construct: function() {
        this.set('toolbar', NamedContainerView.create());
		this.set('content', NamedContainerView.create());
		this.set('controls', Menu.create());
	}.on('init'),

	didInsertElement: function() {
		var view = this;
        var toolbar = this.get('toolbar');
		var content = this.get('content');

		var ReplyItem = MenuItem.extend({
            title: 'Reply',
            icon: 'reply',
            classNameBindings: ['className', 'replying:disabled'],
            replying: function() {
                return this.get('parentController.controllers.composer.showing');
            }.property('parentController.controllers.composer.showing'),
            action: function() { view.get('controller').send('reply'); },
            parentController: this.get('controller'),
        });
		this.get('controls').addItem('reply', ReplyItem);

		toolbar.addItem('menu', MenuSplit.extend({
			items: this.get('controls'),
			classNames: ['discussion-controls']
		}));

		toolbar.addItem('scrollbar', DiscussionScrollbar.extend({
            controller: this.get('controller')
        }));
	}

});
