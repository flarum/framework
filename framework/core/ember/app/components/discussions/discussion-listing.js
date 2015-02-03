import Ember from 'ember';

import TaggedArray from '../../utils/tagged-array';
import ActionButton from '../ui/controls/action-button';

export default Ember.Component.extend({

	terminalPostType: 'last',
	countType: 'unread',

	tagName: 'li',
	attributeBindings: ['discussionId:data-id'],
	classNames: ['discussion-summary'],
	classNameBindings: [
		'discussion.isUnread:unread',
		'active'
	],
	layoutName: 'components/discussions/discussion-listing',

	active: function() {
		return this.get('childViews').anyBy('active');
	}.property('childViews.@each.active'),

	displayUnread: function() {
		return this.get('countType') === 'unread' && this.get('discussion.isUnread');
	}.property('countType', 'discussion.isUnread'),

	displayLastPost: function() {
		return this.get('terminalPostType') === 'last' && this.get('discussion.repliesCount');
	}.property('terminalPostType', 'discussion.repliesCount'),

	start: function() {
		return this.get('discussion.isUnread') ? this.get('discussion.readNumber') + 1 : 1;
	}.property('discussion.isUnread', 'discussion.readNumber'),

	discussionId: Ember.computed.alias('discussion.id'),

	relevantPosts: function() {
		if (this.get('controller.show') !== 'posts') {
			return [];
		}
		
		if (this.get('controller.searchQuery')) {
			return this.get('discussion.relevantPosts');
		} else if (this.get('controller.sort') === 'newest' || this.get('controller.sort') === 'oldest') {
			return [this.get('discussion.startPost')];
		} else {
			return [this.get('discussion.lastPost')];
		}
	}.property('discussion.relevantPosts', 'discussion.startPost', 'discussion.lastPost'),
 
	didInsertElement: function() {
		var $this = this.$().css({opacity: 0});

		setTimeout(function() {
			$this.animate({opacity: 1}, 'fast');
		}, 100);

		if (this.get('discussion.isUnread')) {
			this.$().find('.count').tooltip({container: 'body'});
		}

		// var view = this;
		// this.$().find('a.info').click(function() {
			
		// 	view.set('controller.paneShowing', false);
		// });

		// https://github.com/nolimits4web/Framework7/blob/master/src/js/swipeout.js
		// this.$().find('.discussion').on('touchstart mousedown', function(e) {
		// 	var isMoved = false;
	 //    	var isTouched = true;
		// 	var isScrolling = undefined;
		// 	var touchesStart = {
		// 		x: e.type === 'touchstart' ? e.originalEvent.targetTouches[0].pageX : e.pageX,
		// 		y: e.type === 'touchstart' ? e.originalEvent.targetTouches[0].pageY : e.pageY,
		// 	};
		// 	var touchStartTime = (new Date()).getTime();

		//     $(this).on('touchmove mousemove', function(e) {
		//     	if (! isTouched) return;
		//         $(this).find('a.info').removeClass('pressed');
		//     	var touchesNow = {
		// 			x: e.type === 'touchmove' ? e.originalEvent.targetTouches[0].pageX : e.pageX,
		// 			y: e.type === 'touchmove' ? e.originalEvent.targetTouches[0].pageY : e.pageY,
		// 		};
		// 		if (typeof isScrolling === 'undefined') {
		//             isScrolling = !!(isScrolling || Math.abs(touchesNow.y - touchesStart.y) > Math.abs(touchesNow.x - touchesStart.x));
		//         }
		//         if (isScrolling) {
		//             isTouched = false;
		//             return;
		//         }

		//         isMoved = true;
		//         e.preventDefault();

		//         var diffX = touchesNow.x - touchesStart.x;
		//         var translate = diffX;
		//         var actionsRightWidth = 150;

		//         if (translate < -actionsRightWidth) {
	 //                translate = -actionsRightWidth - Math.pow(-translate - actionsRightWidth, 0.8);
	 //            }

		// 		$(this).css('left', translate);
		//     });

		//     $(this).on('touchend mouseup', function(e) {
		//     	$(this).off('touchmove mousemove touchend mouseup');
		//     	$(this).find('a.info').removeClass('pressed');
		//     	if (!isTouched || !isMoved) {
		//             isTouched = false;
		//             isMoved = false;
		//             return;
		//         }
		//         isTouched = false;
		//         // isMoved = false;

		//     	if (isMoved) {
		//     		e.preventDefault();
		// 			$(this).animate({left: -150});
		//     	}
		//     });
		//     $(this).find('a.info').addClass('pressed').on('click', function(e) {
		//     	if (isMoved) {
		//     		e.preventDefault();
		//     		e.stopImmediatePropagation();
		//     	}
		//     	$(this).off('click');
		//     });
		// });


		this.set('controls', TaggedArray.create());
	},

	populateControlsDefault: function(controls) {
        controls.pushObjectWithTag(ActionButton.create({
        	label: 'Delete',
        	icon: 'times',
        	className: 'delete'
        }), 'delete');
	}.on('populateControls'),

	actions: {
		populateControls: function() {
			if ( ! this.get('controls.length')) {
				this.trigger('populateControls', this.get('controls'));
			}
		}
	}
	
});
