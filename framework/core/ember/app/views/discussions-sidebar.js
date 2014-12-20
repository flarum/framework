import Ember from 'ember';

import NamedContainerView from '../utils/named-container-view';
import Menu from '../utils/menu';
import NavItem from '../components/nav-item';
import ButtonItem from '../components/button-item';
import MenuList from '../components/menu-list';
import ItemCollection from '../components/item-collection';

export default Ember.View.extend({

	// NamedContainerView which will be rendered in the template.
	content: null,

	template: Ember.Handlebars.compile('{{menu-list items=view.toolbar class="toolbar"}}{{menu-list items=view.content class="body"}}'),

	construct: function() {
		this.set('toolbar', NamedContainerView.create());
		this.set('content', NamedContainerView.create());
	}.on('init'),

	didInsertElement: function() {
		var self = this;
		var content = this.get('content');
		var toolbar = this.get('toolbar');

		// Add the 'New Discussion' button. When clicked, this will trigger the
		// application's composer or something
		toolbar.addItem('newDiscussion', ButtonItem.extend({
			title: 'New Discussion',
			icon: 'plus',
			class: 'btn-default btn-block',
			action: function() {
				self.set('controller.controllers.composer.showing', true);
			},
            disabled: function() {
                return this.get('parentController.controllers.composer.showing');
            }.property('parentController.controllers.composer.showing'),
			parentController: this.get('controller')
		}));

		// Add the discussions navigation list.
		var nav = Menu.create();

		nav.addItem('all', NavItem.extend({
			title: 'All Discussions',
			icon: 'comments-o',
			linkTo: '"discussions" (query-params filter="")'
		}));

		nav.addItem('private', NavItem.extend({
			title: 'Private',
			icon: 'envelope-o',
			linkTo: '"discussions" (query-params filter="private")'
		}));

		nav.addItem('following', NavItem.extend({
			title: 'Following',
			icon: 'star',
			linkTo: '"discussions" (query-params filter="following")'
		}));

		nav.addItem('categories', NavItem.extend({
			title: 'Categories',
			icon: 'reorder',
			linkTo: '"categories"'
		}));

		content.addItem('nav', ItemCollection.extend({classNames: ['nav-list'], items: nav}));

		// var tree = {
		// 	'Flarum': ['Announcements', 'General', 'Support', 'Feedback'],
		// 	'Extend': ['Core', 'Plugins', 'Themes']
		// };
		
		var tree = {
			'Ninetech': ['Announcements', 'Sales', 'General', 'Off-Topic'],
			'Development': ['Getting Started', 'Databases', 'Targets', 'Add-Ons']
		};

		// var tree = {
		// 	'TV Addicts': ['General'],
		// 	'Shows': ['Breaking Bad', 'Game of Thrones', 'Doctor Who', 'Sherlock', 'Arrested Development', '72 more...']
		// };

		// var tree = {
		// 	'Categories': ['GameToAid', 'General', 'Journals', 'Gaming', 'Technology', 'Music', 'Movies, TV & Books']
		// };

		// var tree = {
		// 	'Society': ['News', 'Committee', 'General'],
		// 	'Year Levels': ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year', 'Sixth Year', 'Honours']
		// };

		var items = Menu.create();

		var CategoryNavItem = NavItem.extend({
			iconTemplate: function() {
				return '';
				// return '<i class="color category-'+this.get('title').toLowerCase()+'"></i>';
			}.property('title'),
			linkTo: '"discussions" (query-params filter="category")'
		});

		for (var section in tree) {
			var categories = tree[section];
			var categoryItems = Menu.create();

			categories.forEach(function(category) {
				categoryItems.addItem(category.replace(/\./g, ''), CategoryNavItem.extend({
					title: category
				}));
			});

			items.addItem(section, Ember.View.extend({
				tagName: 'li',
				template: Ember.Handlebars.compile('<span class="header">{{view.title}}</span>{{item-collection items=view.items}}'),
				title: section,
				items: categoryItems
			}));
		}

		content.addItem('categories', ItemCollection.extend({classNames: ['nav-list', 'nav-list-small', 'categories'], items: items}));

	}

});
