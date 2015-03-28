import Ember from 'ember';

/**
  A set of fields with a heading.
 */
export default Ember.Component.extend({
  layoutName: 'components/ui/field-set',
  tagName: 'fieldset',
  classNameBindings: ['className'],

  label: '',
  fields: []
});
