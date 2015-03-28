import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import SearchInput from 'flarum/components/ui/search-input';
import UserNotifications from 'flarum/components/application/user-notifications';
import UserDropdown from 'flarum/components/application/user-dropdown';
import ForumStatistic from 'flarum/components/application/forum-statistic';
import PoweredBy from 'flarum/components/application/powered-by';

var $ = Ember.$;

export default Ember.View.extend(HasItemLists, {
  itemLists: ['headerPrimary', 'headerSecondary', 'footerPrimary', 'footerSecondary'],

  title: Ember.computed.alias('controller.forumTitle'),

  // When either the forum title or the page title changes, we want to
  // refresh the document's title.
  updateTitle: Ember.observer('controller.pageTitle', 'controller.forumTitle', function() {
    var parts = [this.get('controller.forumTitle')];
    var pageTitle = this.get('controller.pageTitle');
    if (pageTitle) {
      parts.unshift(pageTitle);
    }
    document.title = parts.join(' - ');
  }),

  modalShowingChanged: Ember.observer('controller.modalController', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      this.$('#modal').modal(this.get('controller.modalController') ? 'show' : 'hide');
    });
  }),

  drawerShowingChanged: Ember.observer('controller.drawerShowing', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      $('body').toggleClass('drawer-open', this.get('controller.drawerShowing'));
    });
  }),

  didInsertElement: function() {
    // Add a class to the body when the window is scrolled down.
  	$(window).scroll(function() {
  		$('body').toggleClass('scrolled', $(window).scrollTop() > 0);
  	}).scroll();

    // Resize the main content area so that the footer sticks to the
    // bottom of the viewport.
    // $(window).resize(function() {
    //   $('#main').css('min-height', $(window).height() - $('#header').outerHeight() - $('#footer').outerHeight(true));
    // }).resize();

    var view = this;
    this.$('#modal').on('hide.bs.modal', function() {
      view.get('controller').send('closeModal');
    }).on('hidden.bs.modal', function() {
      view.get('controller').send('destroyModal');
    }).on('shown.bs.modal', function() {
      view.get('controller.modalController').send('focus');
    });

    this.$().on('show.bs.dropdown', function() {
      $('body').addClass('dropdown-open');
    }).on('hide.bs.dropdown', function() {
      $('body').removeClass('dropdown-open');
    });

    this.$('.global-content').click(function(e) {
      if (view.get('controller.drawerShowing')) {
        e.preventDefault();
        view.set('controller.drawerShowing', false);
      }
    });
  },

  switchHeader: Ember.observer('controller.session.user', function() {
    this.initItemList('headerPrimary');
    this.initItemList('headerSecondary');
  }),

  populateHeaderSecondary: function(items) {
  	var controller = this.get('controller');

  	items.pushObjectWithTag(SearchInput.extend({
      placeholder: 'Search Forum',
      controller: controller,
      valueBinding: Ember.Binding.oneWay('controller.searchQuery'),
      activeBinding: Ember.Binding.oneWay('controller.searchActive'),
      action: function(value) { controller.send('search', value); }
    }), 'search');

    if (this.get('controller.session.isAuthenticated')) {
      items.pushObjectWithTag(UserNotifications.extend({
        user: this.get('controller.session.user'),
        parentController: controller
      }), 'notifications');

      items.pushObjectWithTag(UserDropdown.extend({
        user: this.get('controller.session.user'),
        parentController: controller
      }), 'user');
    } else {
      this.addActionItem(items, 'signup', 'Sign Up').reopen({className: 'btn btn-link'});
      this.addActionItem(items, 'login', 'Log In').reopen({className: 'btn btn-link'});
    }
  },

  populateFooterPrimary: function(items) {
    var addStatistic = function(label, number) {
      items.pushObjectWithTag(ForumStatistic.extend({
        label: label,
        number: number
      }), 'statistics.'+label);
    };
    // addStatistic('discussions', 12);
    // addStatistic('posts', 12);
    // addStatistic('users', 12);
    // addStatistic('online', 12);
  },

  populateFooterSecondary: function(items) {
    items.pushObjectWithTag(PoweredBy, 'poweredBy');
  }
});
