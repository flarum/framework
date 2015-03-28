import Ember from 'ember';

var $ = Ember.$;

/**
  Component for the meta part of a post header. Displays the time, and when
  clicked, shows a dropdown containing more information about the post
  (number, full time, permalink).
 */
export default Ember.Component.extend({
  tagName: 'li',
  classNames: ['dropdown'],
  layoutName: 'components/discussion/post-header/meta',

  // Construct a permalink by looking up the router in the container, and
  // using it to generate a link to this post within its discussion.
  permalink: Ember.computed('post.discusion', 'post.number', function() {
    var router = this.get('controller').container.lookup('router:main');
    var path = router.generate('discussion', this.get('post.discussion'), {queryParams: {start: this.get('post.number')}});
    return window.location.origin+path;
  }),

  didInsertElement: function() {
    // When the dropdown menu is shown, select the contents of the permalink
    // input so that the user can quickly copy the URL.
    var component = this;
    this.$('.dropdown-toggle').click(function() {
      setTimeout(function() { component.$('.permalink').select(); }, 1);
    });

    // Prevent clicking on the input from closing it.
    this.$('.permalink').click(function(e) {
      e.stopPropagation();
    });

    this.set('touch', 'ontouchstart' in document.documentElement);
  }
});
