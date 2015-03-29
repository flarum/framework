import Ember from 'ember';

import HasItemLists from 'flarum-forum/mixins/has-item-lists';
import NavItem from 'flarum-forum/components/ui/nav-item';
import DropdownSelect from 'flarum-forum/components/ui/dropdown-select';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.View.extend(HasItemLists, {
  itemLists: ['sidebar'],

  didInsertElement: function() {
    // Affix the sidebar so that when the user scrolls down it will stick
    // to the top of their viewport.
    var $sidebar = this.$('.user-nav');
    $sidebar.find('> ul').affix({
      offset: {
        top: function () {
          return $sidebar.offset().top - $('#header').outerHeight(true) - parseInt($sidebar.css('margin-top'));
        },
        bottom: function () {
          return (this.bottom = $('#footer').outerHeight(true));
        }
      }
    });
  },

  populateSidebar: function(items) {
    var nav = this.populateItemList('nav');
    items.pushObjectWithTag(DropdownSelect.extend({items: nav, listItemClass: 'title-control'}), 'nav');
  },

  populateNav: function(items) {
    var HasUser = Ember.Mixin.create({
      parentController: this.get('controller'),
      user: Ember.computed.alias('parentController.model')
    });

    items.pushObjectWithTag(NavItem.extend(HasUser, {
      label: 'Activity',
      icon: 'user',
      layout: precompileTemplate('{{#link-to "user.activity" (query-params filter="")}}{{fa-icon icon}} {{label}}{{/link-to}}')
    }), 'activity');

    items.pushObjectWithTag(NavItem.extend(HasUser, {
      label: 'Discussions',
      icon: 'reorder',
      badge: Ember.computed.alias('user.discussionsCount'),
      layout: precompileTemplate('{{#link-to "user.activity" (query-params filter="discussions")}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'discussions');

    items.pushObjectWithTag(NavItem.extend(HasUser, {
      label: 'Posts',
      icon: 'comment-o',
      badge: Ember.computed.alias('parentController.model.commentsCount'),
      layout: precompileTemplate('{{#link-to "user.activity" (query-params filter="posts")}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'posts');

    this.addSeparatorItem(items);

    if (this.get('controller.model') === this.get('controller.session.user')) {
      items.pushObjectWithTag(NavItem.extend({
        label: 'Settings',
        icon: 'cog',
        layout: precompileTemplate('{{#link-to "user.settings"}}{{fa-icon icon}} {{label}}{{/link-to}}')
      }), 'settings');
    }
  }
});
