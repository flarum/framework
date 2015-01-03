import Ember from 'ember';

import TaggedArray from '../utils/tagged-array';
import ActionButton from '../components/ui/controls/action-button';
import DropdownSplit from '../components/ui/controls/dropdown-split';
import DropdownButton from '../components/ui/controls/dropdown-button';
import DiscussionScrollbar from '../components/discussions/stream-scrollbar';
import PostStreamMixin from '../mixins/post-stream';

export default Ember.View.extend(Ember.Evented, PostStreamMixin, {

    sidebarItems: Ember.ContainerView,

    // Set up a new menu view that will contain controls to be shown in the
    // footer. The template will only render these controls if the last post is
    // showing.
    construct: function() {
        // this.set('footerControls', this.createChildView(Menu));
        // this.set('footerControls.controller', this.get('controller'));
        // console.log(this.get('controller'));
    }.on('init'),

    // Whenever the model's title changes, we want to update that document's
    // title the reflect the new title.
    updateTitle: function() {
        this.set('controller.controllers.application.pageTitle', this.get('controller.model.title'));
    }.observes('controller.model.title'),

    didInsertElement: function() {

        // We've just inserted the discussion view.
        // this.trigger('populateSidebar', this.get('sidebar'));

        // Whenever the window's scroll position changes, we want to check to
        // see if any terminal 'gaps' are in the viewport and trigger their
        // loading mechanism if they are. We also want to update the
        // controller's 'start' query param with the current position.
        $(window).on('scroll', {view: this}, this.windowWasScrolled);

        // We need to listen for some events on the controller. Whenever the
        // controller says that it's loading or has loaded posts near a certain
        // post number, we want to scroll down to this post (or the gap which
        // the post is in) and highlight it.
        var controller = this.get('controller');
        controller.on('loadingNumber', this, this.loadingNumber);
        controller.on('loadedNumber', this, this.loadedNumber);
        controller.on('loadingIndex', this, this.loadingIndex);
        controller.on('loadedIndex', this, this.loadedIndex);
    },

    willDestroyElement: function() {
        $(window).off('scroll', this.windowWasScrolled);

        var controller = this.get('controller');
        controller.off('loadingNumber', this, this.loadingNumber);
        controller.off('loadedNumber', this, this.loadedNumber);
        controller.off('loadingIndex', this, this.loadingIndex);
        controller.off('loadedIndex', this, this.loadedIndex);
    },

    setupSidebar: function(sidebar) {
        var items = TaggedArray.create();
        this.trigger('populateControls', items);
        sidebarItems.pushObject(DropdownSplit.create({
            items: items,
            icon: 'reply',
            buttonClass: 'btn-primary',
            menuClass: 'pull-right'
        }), 'controls');

        sidebar.pushObject(DropdownButton.create({items: this.get('controls')}));

        sidebar.pushObject(DiscussionScrollbar.create());
    }.on('populateSidebar'),

    setupControls: function(controls) {
        var view = this;
        var ReplyItem = MenuItem.extend({
            title: 'Reply',
            icon: 'reply',
            classNameBindings: ['className', 'replying:disabled'],
            replying: function() {
                return this.get('parentController.controllers.composer.showing');
            }.property('parentController.controllers.composer.showing'),
            action: function() {
                var lastPost = $('.posts .item:last');
                $('html, body').animate({scrollTop: lastPost.offset().top + lastPost.outerHeight() - $(window).height() + $('.composer').height() + 19}, 'fast');
                view.get('controller').send('reply');
            },
            parentController: this.get('controller'),
        });
        controls.addItem('reply', ReplyItem);
    }.on('populateControls'),

    // This function handles the window's scroll event. We check to see if any
    // terminal 'gaps' are in the viewport and trigger their loading mechanism
    // if they are. We also update the controller's 'start' query param with the
    // current position.
    windowWasScrolled: function(event) {
        var view = event.data.view;

        if (! view.get('controller.loaded') || $(window).data('disableScrollHandler')) {
            return;
        }

        var posts           = view.$().find('.posts'),
            $this           = $(this),
            scrollTop       = $this.scrollTop(),
            viewportHeight  = $this.height(),
            firstItem       = posts.find('.item[data-start=0]'),
            firstItemOffset = firstItem.length ? firstItem.offset().top : 0,
            currentNumber;

        // Loop through each of the items in the discussion. An 'item' is
        // either a single post or a 'gap' of one or more posts that haven't
        // been loaded yet.
        posts.find('.item').each(function() {
            var $this  = $(this),
                top    = $this.offset().top - firstItemOffset,
                height = $this.outerHeight();

            // If this item is above the top of the viewport, skip to the
            // next one. If it's below the bottom of the viewport, break
            // out of the loop.
            if (top + height < scrollTop) {
                return;
            }
            if (top > scrollTop + viewportHeight) {
                return false;
            }

            // Now we know that this item is in the viewport. If we haven't
            // yet stored a post's number, then this item must be the FIRST
            // item in the viewport. Therefore, we'll grab its post number
            // so we can update the controller's state later.
            ! currentNumber && (currentNumber = $this.data('number'));

            // If this item is a gap, then we may proceed to check if it's
            // a *terminal* gap and trigger its loading mechanism.
            var gapView;
            if ($this.hasClass('gap') && (gapView = Ember.View.views[$this.attr('id')])) {
                if ($this.is(':first-of-type')) {
                    gapView.set('direction', 'up').load();
                }
                else if ($this.is(':last-of-type')) {
                    gapView.set('direction', 'down').load();
                }
            }
        });

        // Finally, we want to update the controller's state with regards to the
        // current viewing position of the discussion. However, we don't want to
        // do this on every single scroll event as it will slow things down. So,
        // let's do it at a minimum of 250ms by clearing and setting a timeout.
        clearTimeout(this.updateStateTimeout);
        this.updateStateTimeout = setTimeout(function() {
            view.get('controller').set('start', currentNumber || 1);
        }, 250);
    },

    loadingNumber: function(number) {
        // The post with this number is being loaded. We want to scroll to where
        // we think it will appear. We may be scrolling to the edge of the page,
        // but we don't want to trigger any terminal post gaps to load by doing
        // that. So, we'll disable the window's scroll handler for now.
        $(window).data('disableScrollHandler', true);

        this.jumpToNumber(number);
    },

    loadedNumber: function(number) {
        // The post with this number has been loaded. After we scroll to this
        // post, we want to resume scroll events.
        var view = this;
        this.jumpToNumber(number, function() {
            $(window).data('disableScrollHandler', false).scroll();
        });
    },

    // Scroll down to a certain post (or the gap which the post is in) and
    // highlight it.
    jumpToNumber: function(number, finish) {
        // Clear the highlight class from all posts, and attempt to find and
        // highlight a post with the specified number.
        var item = this.$()
            .find('.posts .item').removeClass('highlight')
            .filter('[data-number='+number+']');

        if (number > 1) {
            item.addClass('highlight');
        }

        // If we didn't have any luck, then a post with this number either
        // doesn't exist, or it hasn't been loaded yet. We'll find the item
        // that's closest to the post with this number and scroll to that
        // instead.
        if (! item.length) {
            item = this.findNearestToNumber(number);
        }

        // We have an item to scroll to now. Let's get its position and animate
        // a scroll-down!
        if (item.length) {
            $('html, body').stop(true).animate({scrollTop: number > 1 ? item.offset().top : 0});
        }
        if (finish) {
            $('html, body').promise().done(finish);
        }
    },

    loadingIndex: function(index) {
        // The post at this index is being loaded. We want to scroll to where we
        // think it will appear. We may be scrolling to the edge of the page,
        // but we don't want to trigger any terminal post gaps to load by doing
        // that. So, we'll disable the window's scroll handler for now.
        $(window).data('disableScrollHandler', true);

        this.jumpToIndex(index);
    },

    loadedIndex: function(index) {
        // The post at this index has been loaded. After we scroll to this post,
        // we want to resume scroll events.
        var view = this;
        this.jumpToIndex(index, function() {
            $(window).data('disableScrollHandler', false).scroll();
        });
    },

    jumpToIndex: function(index, finish) {
        var item = this.findNearestToIndex(index);

        // We have an item to scroll to now. Let's get its position and animate
        // a scroll-down!
        if (item.length) {
            $('html, body').stop(true).animate({scrollTop: index > 0 ? item.offset().top : 0});
        }
        if (finish) {
            $('html, body').promise().done(finish);
        }
    },

    // Right after the controller finished loading a discussion, we want to
    // trigger a scroll event on the window so the interface is kept up-to-date.
    loadedChanged: function() {
        if (this.get('controller.loaded')) {
            Ember.run.scheduleOnce('afterRender', function() {
                $(window).scroll();
            });
        }
    }.observes('controller.loaded')
});
