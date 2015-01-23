import JsonApiAdapter from 'ember-json-api/json-api-adapter';
import config from '../config/environment';

export default JsonApiAdapter.extend({

	// Todo: make this loaded via an environment variable or something
	host: config.apiURL
	
});