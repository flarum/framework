import Ember from 'ember';

import ComposerBody from 'flarum/components/composer/composer-body';

var precompileTemplate = Ember.Handlebars.compile;

/**
  The composer body for posting a reply. Adds a title control to indicate
  which discussion is being replied to.
 */
export default ComposerBody.extend({
  submitLabel: 'Post Reply',

  populateControls: function(items) {
    var title = Ember.Component.create({
      tagName: 'h3',
      layout: precompileTemplate('Replying to <em>{{component.discussion.title}}</em>'),
      component: this
    });
    items.pushObjectWithTag(title, 'title');
  }
});
