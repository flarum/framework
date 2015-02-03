import DS from 'ember-data';
import JsonApiAdapter from 'ember-json-api/json-api-adapter';
import config from '../config/environment';

export default JsonApiAdapter.extend({
  host: config.apiURL,

  // We can get rid of this after
  // https://github.com/kurko/ember-json-api/pull/59 is merged.
  extractMeta: function(store, type, payload) {
    if (payload && payload.meta) {
      store.metaForType(type, payload.meta);
      delete payload.meta;
    }
  },

  ajaxError: function(jqXHR) {
    var errors = this._super(jqXHR);
    if (errors instanceof DS.InvalidError) {
      var newErrors = {};
      for (var i in errors.errors) {
        var error = errors.errors[i];
        newErrors[error.path] = error.detail;
      }
      errors = new DS.InvalidError(newErrors);
    } else if (errors instanceof JsonApiAdapter.ServerError) {
      // @todo show an alert message
      console.log(errors);
    }
    return errors;
  }
  
});