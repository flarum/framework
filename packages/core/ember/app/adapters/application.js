import DS from 'ember-data';
import JsonApiAdapter from 'ember-json-api/json-api-adapter';

import config from 'flarum/config/environment';
import AlertMessage from 'flarum/components/ui/alert-message';

export default JsonApiAdapter.extend({
  host: config.apiURL,

  ajaxError: function(jqXHR) {
    var errors = this._super(jqXHR);

    // Reparse the errors in accordance with the JSON-API spec to fit with
    // Ember Data style. Hopefully something like this will eventually be a
    // part of the JsonApiAdapter.
    if (errors instanceof DS.InvalidError) {
      var newErrors = {};
      for (var i in errors.errors) {
        var error = errors.errors[i];
        newErrors[error.path] = error.detail;
      }
      return new DS.InvalidError(newErrors);
    }

    // If it's a server error, show an alert message. The alerts controller
    // has been injected into this adapter.
    if (errors instanceof JsonApiAdapter.ServerError) {
      var message;
      if (errors.status === 401) {
        message = 'You don\'t have permission to do this.';
      } else {
        message = errors.message;
      }
      var alert = AlertMessage.extend({
        type: 'warning',
        message: message
      });
      this.get('alerts').send('alert', alert);
    }

    return errors;
  }
});
