import Ember from 'ember';

import FadeIn from 'flarum/mixins/fade-in';
import HasItemLists from 'flarum/mixins/has-item-lists';

var precompileTemplate = Ember.Handlebars.compile;

/**
  Component for a `renamed`-typed post.
 */
export default Ember.Component.extend(FadeIn, HasItemLists, {
  layoutName: 'components/discussion/post-renamed',
  tagName: 'article',
  classNames: ['post', 'post-renamed', 'post-activity'],
  itemLists: ['controls'],

  // The stream-content component instansiates this component and sets the
  // `content` property to the content of the item in the post-stream object.
  // This happens to be our post model!
  post: Ember.computed.alias('content'),

  decodedContent: Ember.computed('post.content', function() {
    return JSON.parse(this.get('post.content'));
  }),
  oldTitle: Ember.computed.alias('decodedContent.0'),
  newTitle: Ember.computed.alias('decodedContent.1'),

  populateControls: function(items) {
    this.addActionItem(items, 'delete', 'Delete', 'times', 'post.canDelete');
  },

  actions: {
    // In the template, we render the "controls" dropdown with the contents of
    // the `renderControls` property. This way, when a post is initially
    // rendered, it doesn't have to go to the trouble of rendering the
    // controls right away, which speeds things up. When the dropdown button
    // is clicked, this will fill in the actual controls.
    renderControls: function() {
      this.set('renderControls', this.get('controls'));
    },

    delete: function() {
      var post = this.get('post');
      post.destroyRecord();
      this.sendAction('postRemoved', post);
    }
  }
});
