import Ember from 'ember';

var precompileTemplate = Ember.Handlebars.compile;

/**
  A basic select input. Wraps Ember's select component with a span/icon so
  that we can style it more fancily.
 */
export default Ember.Component.extend({
  layout: precompileTemplate('{{view "select" content=view.content optionValuePath=view.optionValuePath optionLabelPath=view.optionLabelPath value=view.value class="form-control"}} {{fa-icon "sort"}}'),
  tagName: 'span',
  classNames: ['select-input'],

  optionValuePath: 'content',
  optionLabelPath: 'content'
});
