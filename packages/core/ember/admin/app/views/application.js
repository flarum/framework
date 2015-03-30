import Ember from 'ember';

import HasItemLists from '../mixins/has-item-lists';
import AdminNavItem from '../components/ui/admin-nav-item';
import SearchInput from '../components/ui/search-input';
import UserDropdown from '../components/user-dropdown';

export default Ember.View.extend(HasItemLists, {
  itemLists: ['headerPrimary', 'headerSecondary', 'adminNav'],

  drawerShowingChanged: Ember.observer('controller.drawerShowing', function() {
    Ember.run.scheduleOnce('afterRender', this, function() {
      $('body').toggleClass('drawer-open', this.get('controller.drawerShowing'));
    });
  }),

  didInsertElement: function() {
    this.$('.global-content').click(function(e) {
      if (view.get('controller.drawerShowing')) {
        e.preventDefault();
        view.set('controller.drawerShowing', false);
      }
    });
  },

  populateHeaderSecondary: function(items) {
    var controller = this.get('controller');

    items.pushObjectWithTag(SearchInput.extend({
      placeholder: 'Search Forum',
      controller: controller,
      valueBinding: Ember.Binding.oneWay('controller.searchQuery'),
      activeBinding: Ember.Binding.oneWay('controller.searchActive'),
      action: function(value) { controller.send('search', value); }
    }), 'search');

    items.pushObjectWithTag(UserDropdown.extend({
      user: this.get('controller.session.user'),
      parentController: controller
    }), 'user');
  },

  populateAdminNav: function(items) {
    items.pushObjectWithTag(AdminNavItem.extend({
      routeName: 'dashboard',
      icon: 'bar-chart',
      label: 'Dashboard',
      description: 'Your forum at a glance.'
    }), 'dashboard');

    items.pushObjectWithTag(AdminNavItem.extend({
      routeName: 'basics',
      icon: 'pencil',
      label: 'Basics',
      description: 'Set your forum title, language, and other basic settings.'
    }), 'basics');

    items.pushObjectWithTag(AdminNavItem.extend({
      routeName: 'permissions',
      icon: 'key',
      label: 'Permissions',
      description: 'Configure who can see and do what.'
    }), 'permissions');

    items.pushObjectWithTag(AdminNavItem.extend({
      routeName: 'appearance',
      icon: 'paint-brush',
      label: 'Appearance',
      description: 'Customize your forum\'s colors, logos, and other variables.'
    }), 'appearance');

    items.pushObjectWithTag(AdminNavItem.extend({
      routeName: 'extensions',
      icon: 'puzzle-piece',
      label: 'Extensions',
      description: 'Add extra functionality to your forum and make it your own.'
    }), 'extensions');
  }
});
