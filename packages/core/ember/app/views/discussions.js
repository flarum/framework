import Ember from 'ember';

import DropdownSelect from '../components/dropdown-select';
import ButtonItem from '../components/button-item';
import NavItem from '../components/nav-item';
import Menu from '../utils/menu';

export default Ember.View.extend({

	sidebarView: Ember.ContainerView.extend(),

	classNameBindings: ['pinned'],

    pinned: function() {
        return this.get('controller.panePinned');
    }.property('controller.panePinned'),

	didInsertElement: function() {

		this.trigger('populateSidebar', this.get('sidebar'));

		var view = this;

		this.$().find('.discussions-pane').on('mouseenter', function() {
			if (! $(this).hasClass('paned')) return;
			clearTimeout(view.get('controller.paneTimeout'));
	        view.set('controller.paneShowing', true);
		}).on('mouseleave', function() {
            view.set('controller.paneShowing', false);
		});

		if (this.get('controller.test') !== null) {
			var row = this.$().find('li[data-id='+this.get('controller.controllers.application.resultStream.currentResult.id')+']');
			if (row.length) {
				row.addClass('highlight');
			}
			// TODO: work out if the highlighted row is in view of the saved scroll position.
			// If it isn't, don't use the saved scroll position - generate a new one.
			$(window).scrollTop(this.get('controller.test'));
			this.set('controller.test', null);
		}

		var self = this;

		$(window).on('scroll.loadMore', function() {
			if (self.get('controller.loadingMore') || ! self.get('controller.moreResults')) {
				return;
			}

			var w = $(window),
			    d = $('.discussions'),
			    curPos = w.scrollTop() + w.height(),
			    endPos = d.offset().top + d.height() - 200;

			if (curPos > endPos) {
				self.get('controller').send('loadMore');
			}
		});
	},

	setupSidebar: function(sidebar) {
        sidebar.pushObject(ButtonItem.create({
        	title: 'Start a Discussion',
        	icon: 'edit',
        	class: 'btn-primary'
        }));

        var nav = Menu.create();
        this.trigger('populateNav', nav);
        sidebar.pushObject(DropdownSelect.create({
            items: nav
        }));
    }.on('populateSidebar'),

    setupNav: function(nav) {
        nav.addItem('all', NavItem.create({
			title: 'All Discussions',
			icon: 'comments-o',
			linkTo: '"discussions" (query-params filter="")'
		}));

		nav.addItem('private', NavItem.create({
			title: 'Private',
			icon: 'envelope-o',
			linkTo: '"discussions" (query-params filter="private")'
		}));

		nav.addItem('following', NavItem.create({
			title: 'Following',
			icon: 'star',
			linkTo: '"discussions" (query-params filter="following")'
		}));

		nav.addItem('categories', NavItem.create({
			title: 'Categories',
			icon: 'reorder',
			linkTo: '"categories"'
		}));
    }.on('populateNav'),

	willDestroyElement: function() {
		this.set('controller.test', $(window).scrollTop());
		$(window).off('scroll.loadMore');
	}

});
