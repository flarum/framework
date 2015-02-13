import Ember from 'ember';

import UseComposer from 'flarum/mixins/use-composer';
import FadeIn from 'flarum/mixins/fade-in';
import HasItemLists from 'flarum/mixins/has-item-lists';
import ComposerEdit from 'flarum/components/composer/composer-edit';
import PostHeaderUser from 'flarum/components/discussion/post-header/user';
import PostHeaderMeta from 'flarum/components/discussion/post-header/meta';
import PostHeaderEdited from 'flarum/components/discussion/post-header/edited';
import PostHeaderToggle from 'flarum/components/discussion/post-header/toggle';

/**
  Component for a `comment`-typed post. Displays a number of item lists
  (controls, header, and footer) surrounding the post's HTML content. Allows
  the post to be edited with the composer, hidden, or restored.
 */
export default Ember.Component.extend(FadeIn, HasItemLists, UseComposer, {
  layoutName: 'components/discussion/post-comment',
  tagName: 'article',
  classNames: ['post', 'post-comment'],
  classNameBindings: [
    'post.isHidden:is-hidden',
    'post.isEdited:is-edited',
    'revealContent:reveal-content'
  ],
  itemLists: ['controls', 'header', 'footer', 'actions'],

  // The stream-content component instansiates this component and sets the
  // `content` property to the content of the item in the post-stream object.
  // This happens to be our post model!
  post: Ember.computed.alias('content'),

  populateControls: function(items) {
    if (this.get('post.isHidden')) {
      this.addActionItem(items, 'restore', 'Restore', 'reply', 'post.canEdit');
      this.addActionItem(items, 'delete', 'Delete Forever', 'times', 'post.canDelete');
    } else {
      this.addActionItem(items, 'edit', 'Edit', 'pencil', 'post.canEdit');
      this.addActionItem(items, 'hide', 'Delete', 'times', 'post.canEdit');
    }
  },

  // Since we statically populated controls based on the value of
  // `post.isHidden`, we'll need to refresh them every time that property
  // changes.
  refreshControls: Ember.observer('post.isHidden', function() {
    this.initItemList('controls');
  }),

  populateHeader: function(items) {
    var properties = this.getProperties('post');
    items.pushObjectWithTag(PostHeaderUser.create(properties), 'user');
    items.pushObjectWithTag(PostHeaderMeta.create(properties), 'meta');
    items.pushObjectWithTag(PostHeaderEdited.create(properties), 'edited');
    items.pushObjectWithTag(PostHeaderToggle.create(properties, {parent: this}), 'toggle');
  },

  savePost: function(post, data) {
    post.setProperties(data);
    return this.saveAndDismissComposer(post);
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

    edit: function() {
      var post = this.get('post');
      var component = this;
      this.showComposer(function() {
        return ComposerEdit.create({
          user: post.get('user'),
          post: post,
          submit: function(data) { component.savePost(post, data); }
        });
      });
    },

    hide: function() {
      var post = this.get('post');
      post.setProperties({
        isHidden: true,
        hideTime: new Date(),
        hideUser: this.get('session.user')
      });
      post.save();
    },

    restore: function() {
      var post = this.get('post');
      post.setProperties({
        isHidden: false,
        hideTime: null,
        hideUser: null
      });
      post.save();
    },

    delete: function() {
      var post = this.get('post');
      post.destroyRecord();
      this.sendAction('postRemoved', post);
    }
  }
});
