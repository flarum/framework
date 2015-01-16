import JsonApiAdapter from 'ember-json-api/json-api-adapter';

export default JsonApiAdapter.extend({

	// Todo: make this loaded via an environment variable or something
	host: '/api'
	
});