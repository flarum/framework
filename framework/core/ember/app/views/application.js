import Ember from 'ember';

import ActionButton from '../components/ui/controls/action-button';
import SearchInput from '../components/ui/controls/search-input';
import DropdownSelect from '../components/ui/controls/dropdown-select';
import TaggedArray from '../utils/tagged-array';

export default Ember.View.extend({

    title: function() {
        return this.get('controller.forumTitle');
    }.property('controller.forumTitle'),

    didInsertElement: function() {
        // Create and populate an array of items to be rendered in the header.
		this.set('headerPrimaryItems', TaggedArray.create());
		this.set('headerSecondaryItems', TaggedArray.create());
		this.trigger('populateHeader', this.get('headerPrimaryItems'), this.get('headerSecondaryItems'));

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
    },

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

        var signUp = ActionButton.create({
        	label: 'Sign Up',
        	className: 'btn btn-link'
        });
        secondary.pushObjectWithTag(signUp, 'signUp');

        var logIn = ActionButton.create({
        	label: 'Log In',
        	className: 'btn btn-link'
        });
        secondary.pushObjectWithTag(logIn, 'logIn');
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
