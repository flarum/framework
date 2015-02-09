import DS from 'ember-data';
import JsonApiAdapter from 'ember-json-api/json-api-adapter';
import config from '../config/environment';

export default JsonApiAdapter.extend({
  host: config.apiURL,

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
