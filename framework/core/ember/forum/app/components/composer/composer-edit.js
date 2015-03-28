import Ember from 'ember';

import ComposerBody from 'flarum/components/composer/composer-body';

var precompileTemplate = Ember.Handlebars.compile;

/**
  The composer body for editing a post. Sets the initial content to the
  content of the post that is being edited, and adds a title control to
  indicate which post is being edited.
 */
export default ComposerBody.extend({
  submitLabel: 'Save Changes',
  content: Ember.computed.oneWay('post.content'),
  originalContent: Ember.computed.oneWay('post.content'),

  populateControls: function(controls) {
    var title = Ember.Component.extend({
      tagName: 'h3',
      layout: precompileTemplate('Editing Post #{{component.post.number}} in <em>{{discussion.title}}</em>'),
      discussion: this.get('post.discussion'),
      component: this
    });
    controls.pushObjectWithTag(title, 'title');
  }
});
