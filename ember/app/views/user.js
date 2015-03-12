import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import NavItem from 'flarum/components/ui/nav-item';
import DropdownSelect from 'flarum/components/ui/dropdown-select';

var precompileTemplate = Ember.Handlebars.compile;

export default Ember.View.extend(HasItemLists, {
  itemLists: ['sidebar'],

  populateSidebar: function(items) {
    var nav = this.populateItemList('nav');
    items.pushObjectWithTag(DropdownSelect.extend({items: nav, listItemClass: 'title-control'}), 'nav');
  },

  populateNav: function(items) {
    items.pushObjectWithTag(NavItem.extend({
      label: 'Activity',
      icon: 'user',
      layout: precompileTemplate('{{#link-to "user.activity"}}{{fa-icon icon}} {{label}}{{/link-to}}')
    }), 'activity');

    items.pushObjectWithTag(NavItem.extend({
      label: 'Discussions',
      icon: 'reorder',
      badge: Ember.computed.alias('user.discussionsCount'),
      user: this.get('controller.model'),
      layout: precompileTemplate('{{#link-to "user.discussions"}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'discussions');

    items.pushObjectWithTag(NavItem.extend({
      label: 'Posts',
      icon: 'comment-o',
      badge: Ember.computed.alias('user.commentsCount'),
      user: this.get('controller.model'),
      layout: precompileTemplate('{{#link-to "user.posts"}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'posts');
  }
});
