import Ember from 'ember';

import DropdownSelect from '../components/ui/controls/dropdown-select';
import ActionButton from '../components/ui/controls/action-button';
import NavItem from '../components/ui/items/nav-item';
import TaggedArray from '../utils/tagged-array';

export default Ember.View.extend({

	sidebarItems: null,

	classNameBindings: ['pinned'],

    pinned: function() {
        return this.get('controller.panePinned');
    }.property('controller.panePinned'),

	didInsertElement: function() {

		var sidebarItems = TaggedArray.create();
		this.trigger('populateSidebar', sidebarItems);
		this.set('sidebarItems', sidebarItems);

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

	populateSidebarDefault: function(sidebar) {
		var newDiscussion = ActionButton.create({
        	title: 'Start a Discussion',
        	icon: 'edit',
        	class: 'btn-primary'
        })
        sidebar.pushObjectWithTag(newDiscussion, 'newDiscussion');

        var nav = TaggedArray.create();
        this.trigger('populateNav', nav);
        sidebar.pushObjectWithTag(DropdownSelect.createWithItems(nav), 'nav');
    }.on('populateSidebar'),

    populateNavDefault: function(nav) {
        nav.pushObjectWithTag(NavItem.create({
			title: 'All Discussions',
			icon: 'comments-o',
			linkTo: '"discussions" (query-params filter="")'
		}), 'all');

		nav.pushObjectWithTag(NavItem.create({
			title: 'Private',
			icon: 'envelope-o',
			linkTo: '"discussions" (query-params filter="private")'
		}), 'private');

		nav.pushObjectWithTag(NavItem.create({
			title: 'Following',
			icon: 'star',
			linkTo: '"discussions" (query-params filter="following")'
		}), 'following');
    }.on('populateNav'),

	willDestroyElement: function() {
		this.set('controller.test', $(window).scrollTop());
		$(window).off('scroll.loadMore');
	}

});
