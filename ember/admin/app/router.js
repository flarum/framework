import Ember from 'ember';
import config from './config/environment';

var Router = Ember.Router.extend({
  location: config.locationType
});

Router.map(function() {
  this.resource('dashboard', {path: '/'});
  this.resource('basics');
  this.resource('permissions');
  this.resource('appearance');
  this.resource('extensions');
});

export default Router;
