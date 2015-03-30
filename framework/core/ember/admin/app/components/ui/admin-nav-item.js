import Ember from 'ember';
import NavItem from './nav-item';

var precompileTemplate = Ember.Handlebars.compile;

export default NavItem.extend({
  layout: precompileTemplate('{{#link-to routeName}}{{fa-icon icon class="icon"}} <span class="label">{{label}}</span> <div class="description">{{description}}</div>{{/link-to}}')
});
