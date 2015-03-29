import Ember from 'ember';

import HasItemLists from 'flarum-forum/mixins/has-item-lists';

/**
  This component is a base class for a composer body. It provides a template
  with a list of controls, a text editor, and some default behaviour.
 */
export default Ember.Component.extend(HasItemLists, {
  layoutName: 'components/composer/composer-body',

  itemLists: ['controls'],

  submitLabel: '',
  placeholder: '',
  content: '',
  originalContent: '',
  user: null,
  submit: null,
  loading: false,
  confirmExit: '',

  disabled: Ember.computed.alias('composer.minimized'),

  actions: {
    submit: function(content) {
      this.get('submit')({
        content: content
      });
    },

    willExit: function(abort) {
      // If the user has typed something, prompt them before exiting
      // this composer state.
      if (this.get('content') !== this.get('originalContent') && !confirm(this.get('confirmExit'))) {
        abort();
      }
    }
  }
});
