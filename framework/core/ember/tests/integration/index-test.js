import Ember from "ember";
import { test } from 'ember-qunit';
import startApp from '../helpers/start-app';
var App;

module('Index', {
  setup: function() {
    App = startApp();
  },
  teardown: function() {
    Ember.run(App, App.destroy);
  }
});

test('Discussion list loading', function() {
  // expect(1);
  visit('/').then(function() {
    // equal(find('.discussions-list').length, 1, "Page contains list of discussions");
  });
});