import Ember from 'ember';

import HasItemLists from 'flarum/mixins/has-item-lists';
import UserBio from 'flarum/components/user/user-bio';

export default Ember.Component.extend(HasItemLists, {
  layoutName: 'components/user/user-card',
  classNames: ['user-card'],
  attributeBindings: ['style'],
  itemLists: ['controls', 'info'],

  style: Ember.computed('user.color', function() {
    return 'background-color: '+this.get('user.color');
  }),

  avatarUrlDidChange: Ember.observer('user.avatarUrl', function() {
    this.refreshOverlay(true);
  }),

  didInsertElement: function() {
    this.refreshOverlay();
  },

  refreshOverlay: function(animate) {
    var component = this;
    var $overlay = component.$('.darken-overlay');
    var $newOverlay = $overlay.clone().removeAttr('style').insertBefore($overlay);
    var avatarUrl = component.get('user.avatarUrl');
    var hideOverlay = function() {
      if (animate) {
        $overlay.fadeOut('slow');
      }
      $overlay.promise().done(function() {
        $(this).remove();
      });
    };

    if (avatarUrl) {
      $('<img>').attr('src', avatarUrl).on('load', function() {
        component.$().css('background-image', 'url('+avatarUrl+')');
        $newOverlay.blurjs({
          source: component.$(),
          radius: 50,
          overlay: 'rgba(0, 0, 0, .2)',
          useCss: false
        });
        component.$().css('background-image', '');
        if (animate) {
          $newOverlay.hide().fadeIn('slow');
        }
        hideOverlay();
      });
    } else {
      hideOverlay();
    }
  },

  populateControls: function(items) {
    this.addActionItem(items, 'edit', 'Edit', 'pencil');
    this.addActionItem(items, 'delete', 'Delete', 'times');
  },

  populateInfo: function(items) {
    if (this.get('user.bioHtml') || (this.get('editable') && this.get('user.canEdit'))) {
      items.pushObjectWithTag(UserBio.extend({
        user: this.get('user'),
        editable: this.get('editable'),
        listItemClass: 'block-item'
      }), 'bio');
    }

    items.pushObjectWithTag(Ember.Component.extend({
      tagName: 'li',
      classNames: ['user-last-seen'],
      classNameBindings: ['hidden', 'user.online:online'],
      layout: Ember.Handlebars.compile('{{#if user.online}}{{fa-icon "circle"}} Online{{else}}{{fa-icon "clock-o"}} {{human-time user.lastSeenTime}}{{/if}}'),
      user: this.get('user'),
      hidden: Ember.computed.not('user.lastSeenTime')
    }), 'lastActiveTime');

    items.pushObjectWithTag(Ember.Component.extend({
      layout: Ember.Handlebars.compile('Joined {{human-time user.joinTime}}'),
      user: this.get('user')
    }), 'joinTime');
  }
});
