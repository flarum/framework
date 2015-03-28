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
  expect(3);
  visit('/').then(function() {
    equal(find('.discussions-list').length, 1, 'Page contains list of discussions');
    equal(find('.discussions-list li').length, 20, 'There are 20 discussions in the list');

    click('.control-loadMore').then(function() {
      equal(find('.discussions-list li').length, 40, 'There are 40 discussions in the list');
    });
  });
});

test('Discussion list sorting', function() {
  expect(1);
  visit('/').then(function() {
    fillIn('.control-sort select', 'replies').then(function() {
      var discussions = find('.discussions-list li');
      var good = true;
      var getCount = function(item) {
        return parseInt(item.find('.count strong').text());
      };
      var previousCount = getCount(discussions.eq(0));
      for (var i = 1; i < discussions.length; i++) {
        var count = getCount(discussions.eq(i));
        if (count > previousCount) {
          good = false;
          break;
        }
        previousCount = count;
      }
      ok(good, 'Discussions are listed in order of reply count');
    });
  });
});