import JsonApiSerializer from 'ember-json-api/json-api-serializer';

export default JsonApiSerializer.extend({
	normalize: function(type, hash, property) {
		var json = {};

		for (var prop in hash) {
			json[prop.camelize()] = hash[prop]; 
		}

		return this._super(type, json, property);
	}
});