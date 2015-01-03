import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';
import ActionButton from '../ui/controls/action-button';

export default Ember.Component.extend({

	_init: function() {
		// this.set('controls', Menu.create());
	}.on('init'),

	tagName: 'li',
	attributeBindings: ['discussionId:data-id'],
	classNameBindings: [
		'discussion.unread:unread',
		'discussion.sticky:sticky',
		'discussion.locked:locked',
		'discussion.following:following',
		'active'
	],
	templateName: 'components/discussions/discussion-listing',

	active: function() {
		return this.get('childViews').anyBy('active');
	}.property('childViews.@each.active'),

	discussionId: function() {
		return this.get('discussion.id');
	}.property('discussion.id'),

	relevantPosts: function() {
		if (this.get('controller.show') != 'posts') return [];
		
		if (this.get('controller.searchQuery')) {
			return this.get('discussion.relevantPosts');
		} else if (this.get('controller.sort') == 'newest' || this.get('controller.sort') == 'oldest') {
			return [this.get('discussion.startPost')];
		} else {
			return [this.get('discussion.lastPost')];
		}
	}.property('discussion.relevantPosts', 'discussion.startPost', 'discussion.lastPost'),

	icon: function() {
		if (this.get('discussion.unread')) return 'circle';
	}.property('discussion.unread'),

	iconAction: function() {
		if (this.get('discussion.unread')) return function() {
			
		};
	}.property('discussion.unread'),

	categoryClass: function() {
		return 'category-'+this.get('discussion.category').toLowerCase();
	}.property('discussion.category'),
 
	didInsertElement: function() {
		this.$().hide().fadeIn('slow');

		this.$().find('.terminal-post a').tooltip();

		var view = this;
		this.$().find('a.info, .terminal-post a').click(function() {
			view.set('controller.paneShowing', false);
		});

		// https://github.com/nolimits4web/Framework7/blob/master/src/js/swipeout.js
		this.$().find('.discussion').on('touchstart mousedown', function(e) {
			var isMoved = false;
	    	var isTouched = true;
			var isScrolling = undefined;
			var touchesStart = {
				x: e.type === 'touchstart' ? e.originalEvent.targetTouches[0].pageX : e.pageX,
				y: e.type === 'touchstart' ? e.originalEvent.targetTouches[0].pageY : e.pageY,
			};
			var touchStartTime = (new Date()).getTime();

		    $(this).on('touchmove mousemove', function(e) {
		    	if (! isTouched) return;
		        $(this).find('a.info').removeClass('pressed');
		    	var touchesNow = {
					x: e.type === 'touchmove' ? e.originalEvent.targetTouches[0].pageX : e.pageX,
					y: e.type === 'touchmove' ? e.originalEvent.targetTouches[0].pageY : e.pageY,
				};
				if (typeof isScrolling === 'undefined') {
		            isScrolling = !!(isScrolling || Math.abs(touchesNow.y - touchesStart.y) > Math.abs(touchesNow.x - touchesStart.x));
		        }
		        if (isScrolling) {
		            isTouched = false;
		            return;
		        }

		        isMoved = true;
		        e.preventDefault();

		        var diffX = touchesNow.x - touchesStart.x;
		        var translate = diffX;
		        var actionsRightWidth = 150;

		        if (translate < -actionsRightWidth) {
	                translate = -actionsRightWidth - Math.pow(-translate - actionsRightWidth, 0.8);
	            }

				$(this).css('left', translate);
		    });

		    $(this).on('touchend mouseup', function(e) {
		    	$(this).off('touchmove mousemove touchend mouseup');
		    	$(this).find('a.info').removeClass('pressed');
		    	if (!isTouched || !isMoved) {
		            isTouched = false;
		            isMoved = false;
		            return;
		        }
		        isTouched = false;
		        // isMoved = false;

		    	if (isMoved) {
		    		e.preventDefault();
					$(this).animate({left: -150});
		    	}
		    });
		    $(this).find('a.info').addClass('pressed').on('click', function(e) {
		    	if (isMoved) {
		    		e.preventDefault();
		    		e.stopImmediatePropagation();
		    	}
		    	$(this).off('click');
		    });
		});

		var discussion = this.get('discussion');

		// var controls = this.get('controls');

		// controls.addItem('sticky', MenuItem.extend({title: 'Sticky', icon: 'thumb-tack', action: 'sticky'}));
		// controls.addItem('lock', MenuItem.extend({title: 'Lock', icon: 'lock', action: 'lock'}));

		// controls.addSeparator();

		// controls.addItem('delete', MenuItem.extend({title: 'Delete', icon: 'times', className: 'delete', action: function() {
		// 	// this.get('controller').send('delete', discussion);
		// 	var discussion = view.$().slideUp().find('.discussion');
		// 	discussion.css('position', 'relative').animate({left: -discussion.width()});
		// }}));
	},

	actions: {
		icon: function() {
			this.get('iconAction')();
		}
	}
	
});
