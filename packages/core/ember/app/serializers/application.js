import JsonApiSerializer from 'ember-json-api/json-api-serializer';

export default JsonApiSerializer.extend({
  normalize: function(type, hash, property) {
    var json = {};

    for (var prop in hash) {
      json[prop.camelize()] = hash[prop];
    }

    return this._super(type, json, property);
  },

  // We can get rid of this after
  // https://github.com/kurko/ember-json-api/pull/59 is merged.
  extractMeta: function(store, type, payload) {
    if (payload && payload.meta) {
      store.setMetadataFor(type, payload.meta);
      delete payload.meta;
    }
  }
});
