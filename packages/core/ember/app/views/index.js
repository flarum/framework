import Ember from 'ember';

import DropdownSelect from 'flarum/components/ui/dropdown-select';
import ActionButton from 'flarum/components/ui/action-button';
import NavItem from 'flarum/components/ui/nav-item';
import WelcomeHero from 'flarum/components/index/welcome-hero';
import HasItemLists from 'flarum/mixins/has-item-lists';

var precompileTemplate = Ember.Handlebars.compile;
var $ = Ember.$;

export default Ember.View.extend(HasItemLists, {
  itemLists: ['sidebar'],

  didInsertElement: function() {
    this.set('hero', WelcomeHero.extend({
      title: this.get('controller.controllers.application.forumTitle'),
      description: 'Thanks for stopping by!'
    }));

    // Affix the sidebar so that when the user scrolls down it will stick
    // to the top of their viewport.
    var $sidebar = this.$('.index-nav');
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

    // When viewing a discussion (for which the discussions route is the
    // parent,) the discussion list is still rendered but it becomes a
    // pane hidden on the side of the screen. When the mouse enters and
    // leaves the discussions pane, we want to show and hide the pane
    // respectively. We also create a 10px 'hot edge' on the left of the
    // screen to activate the pane.
    var controller = this.get('controller');
    this.$('.index-area').hover(function() {
      controller.send('showPane');
    }, function() {
      controller.send('hidePane');
    });
    $(document).on('mousemove.showPane', function(e) {
      if (e.pageX < 10) {
        controller.send('showPane');
      }
    });
  },

  willDestroyElement: function() {
    $(document).off('mousemove.showPane');
  },

  scrollToDiscussion: Ember.observer('controller.paned', function() {
    if (this.get('controller.paned')) {
      var view = this;
      Ember.run.scheduleOnce('afterRender', function() {
        var $index = view.$('.index-area');
        var $discussion = $index.find('.discussion-summary.active');
        if ($discussion.length) {
          var indexTop = $index.offset().top;
          var discussionTop = $discussion.offset().top;
          if (discussionTop < indexTop || discussionTop + $discussion.outerHeight() > indexTop + $index.outerHeight()) {
            $index.scrollTop($index.scrollTop() - indexTop + discussionTop);
          }
        }
      });
    }
  }),

  populateSidebar: function(items) {
    this.addActionItem(items, 'newDiscussion', 'Start a Discussion', 'edit').reopen({className: 'btn btn-primary new-discussion'});

    var nav = this.populateItemList('nav');
    items.pushObjectWithTag(DropdownSelect.extend({items: nav}), 'nav');
  },

  populateNav: function(items) {
    items.pushObjectWithTag(NavItem.extend({
      label: 'All Discussions',
      icon: 'comments-o',
      layout: precompileTemplate('{{#link-to "index" (query-params filter="")}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'all');

    items.pushObjectWithTag(NavItem.extend({
      label: 'Private',
      icon: 'envelope-o',
      layout: precompileTemplate('{{#link-to "index" (query-params filter="private")}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'private');

    items.pushObjectWithTag(NavItem.extend({
      label: 'Following',
      icon: 'star',
      layout: precompileTemplate('{{#link-to "index" (query-params filter="following")}}{{fa-icon icon}} {{label}} <span class="count">{{badge}}</span>{{/link-to}}')
    }), 'following');
  }
});
