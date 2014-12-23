import JsonApiAdapter from 'ember-json-api/json-api-adapter';
export default JsonApiAdapter.extend({
	host: '/api',

	findQuery: function(store, type, query) {
		var ids = null;
		if (query.ids) {
			ids = query.ids.join(',');
			delete query.ids;
		}
		return this.ajax(this.buildURL(type.typeKey, ids), 'GET', {data: query});
	},
});

// export default DS.JsonApiAdapter.extend({
// 	host: '/api',

// 	// xhr: [],

// 	// ajax: function(url, type, hash) {
//  //    var adapter = this;

//  //    return new Ember.RSVP.Promise(function(resolve, reject) {
//  //      hash = adapter.ajaxOptions(url, type, hash);

//  //      hash.success = function(json) {
//  //        Ember.run(null, resolve, json);
//  //      };

//  //      hash.error = function(jqXHR, textStatus, errorThrown) {
//  //        Ember.run(null, reject, adapter.ajaxError(jqXHR));
//  //      };

//  //      adapter.xhr.push(Ember.$.ajax(hash));
//  //    }, "DS: RestAdapter#ajax " + type + " to " + url);
//  //  },
// });
