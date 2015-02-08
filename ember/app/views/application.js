import Ember from 'ember';

import ActionButton from '../components/ui/controls/action-button';
import SearchInput from '../components/ui/controls/search-input';
import DropdownSelect from '../components/ui/controls/dropdown-select';
import DropdownButton from '../components/ui/controls/dropdown-button';
import SeparatorItem from '../components/ui/items/separator-item';
import TaggedArray from '../utils/tagged-array';

var $ = Ember.$;

export default Ember.View.extend({

    // When either the forum title or the page title changes, we want to
    // refresh the document's title.
    updateTitle: function() {
        var parts = [this.get('controller.forumTitle')];
        var pageTitle = this.get('controller.pageTitle');
        if (pageTitle) {
            parts.unshift(pageTitle);
        }
        document.title = parts.join(' - ');
    }.observes('controller.pageTitle', 'controller.forumTitle'),

    title: function() {
        return this.get('controller.forumTitle');
    }.property('controller.forumTitle'),

    modalShowingChanged: function() {
        if (!this.$()) { return; }

        if (this.get('controller.modalController')) {
            this.$('#modal').modal('show');
        } else {
            this.$('#modal').modal('hide');
        }
    }.observes('controller.modalController'),

    didInsertElement: function() {
        

        // Create and populate an array of items to be rendered in the footer.
		this.set('footerPrimaryItems', TaggedArray.create());
		this.set('footerSecondaryItems', TaggedArray.create());
		this.trigger('populateFooter', this.get('footerPrimaryItems'), this.get('footerSecondaryItems'));

        // Add a class to the body when the window is scrolled down.
    	$(window).scroll(function() {
    		$('body').toggleClass('scrolled', $(window).scrollTop() > 0);
    	}).scroll();

        // Resize the main content area so that the footer sticks to the
        // bottom of the viewport.
        $(window).resize(function() {
            $('#main').css('min-height', $(window).height() - $('#header').outerHeight() - $('#footer').outerHeight(true));
        }).resize();

        var view = this;
        this.$('#modal').on('hide.bs.modal', function() {
            view.get('controller').send('closeModal');
        }).on('hidden.bs.modal', function() {
            view.get('controller').send('destroyModal');
        }).on('shown.bs.modal', function() {
            view.get('controller.modalController').send('focus');
        });
    },

    switchHeader: function() {
        // Create and populate an array of items to be rendered in the header.
        this.set('headerPrimaryItems', TaggedArray.create());
        this.set('headerSecondaryItems', TaggedArray.create());
        this.trigger('populateHeader', this.get('headerPrimaryItems'), this.get('headerSecondaryItems'));
    }.observes('controller.session.user'),

    populateHeaderDefault: function(primary, secondary) {
    	var controller = this.get('controller');

    	var search = SearchInput.create({
    		placeholder: 'Search Forum',
    		controller: controller,
    		valueBinding: Ember.Binding.oneWay('controller.searchQuery'),
    		activeBinding: Ember.Binding.oneWay('controller.searchActive'),
    		action: function(value) {
    			controller.send('search', value);
    		}
    	});
    	secondary.pushObjectWithTag(search, 'search');

        if (this.get('controller.session.isAuthenticated')) {
            var userItems = TaggedArray.create();

            var profile = ActionButton.create({
                label: 'Profile',
                icon: 'user'
            });
            userItems.pushObjectWithTag(profile, 'profile');

            var settings = ActionButton.create({
                label: 'Settings',
                icon: 'cog'
            });
            userItems.pushObjectWithTag(settings, 'settings');

            userItems.pushObject(SeparatorItem.create());

            var logOut = ActionButton.create({
                label: 'Log Out',
                icon: 'sign-out',
                action: function() {
                    controller.send('invalidateSession');
                }
            });
            userItems.pushObjectWithTag(logOut, 'logOut');

            var userDropdown = DropdownButton.extend({
                label: Ember.computed.alias('user.username'),
                buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
                buttonPartial: 'partials/user-button',
                menuClass: 'pull-right'
            });
            secondary.pushObjectWithTag(userDropdown.create({
                items: userItems,
                user: this.get('controller.session.user')
            }), 'user');
        } else {
            var signUp = ActionButton.create({
                label: 'Sign Up',
                className: 'btn btn-link',
                action: function() {
                    controller.send('signup');
                }
            });
            secondary.pushObjectWithTag(signUp, 'signUp');

            var logIn = ActionButton.create({
                label: 'Log In',
                className: 'btn btn-link',
                action: function() {
                    controller.send('login');
                }
            });
            secondary.pushObjectWithTag(logIn, 'logIn');
        }
    }.on('populateHeader'),

    populateFooterDefault: function(primary, secondary) {
        primary.pushObjectWithTag(ActionButton.create({
            icon: 'arrow-up',
            action: function() { $('html, body').stop(true).animate({scrollTop: 0}); },
            title: 'Go to Top',
            class: 'control-top'
        }), 'top');

        primary.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('{{discussions}} discussions'),
            discussions: 12
        }), 'statistics.discussions');

        primary.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('{{posts}} posts'),
            posts: 12
        }), 'statistics.posts');

        primary.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('{{users}} users'),
            users: 12
        }), 'statistics.users');

        primary.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('{{online}} online'),
            online: 12
        }), 'statistics.online');

        var languages = TaggedArray.create();
        languages.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('<a href="#">{{label}}</a>'),
            label: 'English',
            tagName: 'li',
            classNameBindings: ['active'],
            active: true
        }));
        secondary.pushObjectWithTag(DropdownSelect.create({
            buttonClass: '',
            class: 'dropup',
            items: languages
        }), 'language');

        secondary.pushObjectWithTag(Ember.Component.create({
            layout: Ember.Handlebars.compile('<a href="http://flarum.org" target="_blank">Powered by Flarum</a>'),
        }), 'poweredBy');
    }.on('populateFooter'),

});
