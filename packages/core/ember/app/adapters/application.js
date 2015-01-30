import JsonApiAdapter from 'ember-json-api/json-api-adapter';
import config from '../config/environment';

export default JsonApiAdapter.extend({

  // Todo: make this loaded via an environment variable or something
  host: config.apiURL,

  // We can get rid of this after
  // https://github.com/kurko/ember-json-api/pull/59 is merged.
  extractMeta: function(store, type, payload) {
    if (payload && payload.meta) {
      store.metaForType(type, payload.meta);
      delete payload.meta;
    }
  }
  
});