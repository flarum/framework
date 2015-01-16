import Ember from 'ember';

import DropdownSelect from '../components/ui/controls/dropdown-select';
import ActionButton from '../components/ui/controls/action-button';
import NavItem from '../components/ui/items/nav-item';
import TaggedArray from '../utils/tagged-array';

export default Ember.View.extend({

	sidebarItems: null,

	didInsertElement: function() {
		// Create and populate an array of items to be rendered in the sidebar.
		var sidebarItems = TaggedArray.create();
		this.trigger('populateSidebar', sidebarItems);
		this.set('sidebarItems', sidebarItems);

		// Affix the sidebar so that when the user scrolls down it will stick
		// to the top of their viewport.
		var $sidebar = this.$().find('.index-nav');
		$sidebar.find('> ul').affix({
			offset: {
				top: function () {
					return $sidebar.offset().top - $('#header').outerHeight(true) - parseInt($sidebar.css('margin-top'));
				},
				bottom: function () {
					return (this.bottom = $('#footer').outerHeight(true))
				}
			}
		});

		// When viewing a discussion (for which the discussions route is the
		// parent,) the discussion list is still rendered but it becomes a
		// pane hidden on the side of the screen. When the mouse enters and
		// leaves the discussions pane, we want to show and hide the pane
		// respectively. We also create a 10px 'hot edge' on the left of the
		// screen to activate the pane.
		var controller = this.get('controller');
		this.$('.index-area').hover(function() {
			controller.send('showPane');
		}, function() {
			controller.send('hidePane');
		});
		$(document).on('mousemove.showPane', function(e) {
            if (e.pageX < 10) {
            	controller.send('showPane');
            }
        });        
	},

	willDestroyElement: function() {
		$(document).off('mousemove.showPane');
	},

	populateSidebarDefault: function(sidebar) {
		var newDiscussion = ActionButton.create({
        	label: 'Start a Discussion',
        	icon: 'edit',
        	className: 'btn btn-primary new-discussion'
        })
        sidebar.pushObjectWithTag(newDiscussion, 'newDiscussion');

        var nav = TaggedArray.create();
        this.trigger('populateNav', nav);
        sidebar.pushObjectWithTag(DropdownSelect.create({
        	items: nav
		}), 'nav');
    }.on('populateSidebar'),

    populateNavDefault: function(nav) {
        nav.pushObjectWithTag(NavItem.create({
			label: 'All Discussions',
			icon: 'comments-o',
			linkTo: '"index" (query-params filter="")'
		}), 'all');

        // The below items are just temporary; they will be extracted into
        // extensions in the future.
		nav.pushObjectWithTag(NavItem.create({
			label: 'Private',
			icon: 'envelope-o',
			linkTo: '"index" (query-params filter="private")'
		}), 'private');

		nav.pushObjectWithTag(NavItem.create({
			label: 'Following',
			icon: 'star',
			linkTo: '"index" (query-params filter="following")'
		}), 'following');
    }.on('populateNav')
});
