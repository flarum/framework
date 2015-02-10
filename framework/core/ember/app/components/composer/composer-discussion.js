import Ember from 'ember';

import ComposerBody from 'flarum/components/composer/composer-body';

var precompileTemplate = Ember.Handlebars.compile;

/**
  The composer body for starting a new discussion. Adds a text field as a
  control so the user can enter the title of their discussion. Also overrides
  the `submit` and `willExit` actions to account for the title.
 */
export default ComposerBody.extend({
  submitLabel: 'Post Discussion',
  confirmExit: 'You have not posted your discussion. Do you wish to discard it?',
  titlePlaceholder: 'Discussion Title',
  title: '',

  populateControls: function(items) {
    var title = Ember.Component.create({
      tagName: 'h3',
      layout: precompileTemplate('{{ui/text-input value=component.title class="form-control" placeholder=component.titlePlaceholder disabled=component.disabled autoGrow=true}}'),
      component: this
    });
    items.pushObjectWithTag(title, 'title');
  },

  actions: {
    submit: function(content) {
      this.get('submit')({
        title: this.get('title'),
        content: content
      });
    },

    willExit: function(abort) {
      if ((this.get('title') || this.get('content')) && !confirm(this.get('confirmExit'))) {
        abort();
      }
    }
  }
});
