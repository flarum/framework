import Ember from 'ember';
import DS from 'ember-data';

export default DS.JsonApiAdapter.extend({
	host: '/api',

	xhr: [],

	ajax: function(url, type, hash) {
    var adapter = this;

    return new Ember.RSVP.Promise(function(resolve, reject) {
      hash = adapter.ajaxOptions(url, type, hash);

      hash.success = function(json) {
        Ember.run(null, resolve, json);
      };

      hash.error = function(jqXHR, textStatus, errorThrown) {
        Ember.run(null, reject, adapter.ajaxError(jqXHR));
      };

      adapter.xhr.push(Ember.$.ajax(hash));
    }, "DS: RestAdapter#ajax " + type + " to " + url);
  },
});
