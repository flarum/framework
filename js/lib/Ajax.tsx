import { app } from 'flarum';
import ItemList from 'flarum/utils/ItemList';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import RequestErrorModal from 'flarum/components/RequestErrorModal';
import ConfirmPasswordModal from 'flarum/components/ConfirmPasswordModal';
import Translator from 'flarum/Translator';
import extract from 'flarum/utils/extract';
import patchMithril from 'flarum/utils/patchMithril';
import RequestError from 'flarum/utils/RequestError';
import { extend } from 'flarum/extend';

export default class Ajax {
  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://lhorie.github.io/mithril/mithril.request.html
   * @param {Object} options
   * @return {Promise}
   * @public
   */
  request(originalOptions) {
    const options = Object.assign({}, originalOptions);

    // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.
    options.background = options.background || true;

    extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken));

    // If the method is something like PATCH or DELETE, which not all servers
    // and clients support, then we'll send it as a POST request with the
    // intended method specified in the X-HTTP-Method-Override header.
    if (options.method !== 'GET' && options.method !== 'POST') {
      const method = options.method;
      extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-HTTP-Method-Override', method));
      options.method = 'POST';
    }

    // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.
    options.deserialize = options.deserialize || (responseText => responseText);

    options.errorHandler = options.errorHandler || (error => {
      throw error;
    });

    // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.
    const original = options.extract;
    options.extract = xhr => {
      let responseText;

      if (original) {
        responseText = original(xhr.responseText);
      } else {
        responseText = xhr.responseText || null;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError(status, responseText, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError(500, responseText, options, xhr);
      }
    };

    if (this.requestError) this.alerts.dismiss(this.requestError.alert);

    // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.
    const deferred = m.deferred();

    m.request(options).then(response => deferred.resolve(response), error => {
      this.requestError = error;

      let children;

      switch (error.status) {
        case 422:
          children = error.response.errors
            .map(error => [error.detail, <br/>])
            .reduce((a, b) => a.concat(b), [])
            .slice(0, -1);
          break;

        case 401:
        case 403:
          children = app.translator.trans('core.lib.error.permission_denied_message');
          break;

        case 404:
        case 410:
          children = app.translator.trans('core.lib.error.not_found_message');
          break;

        case 429:
          children = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
          break;

        default:
          children = app.translator.trans('core.lib.error.generic_message');
      }

      error.alert = new Alert({
        type: 'error',
        children,
        controls: app.forum.attribute('debug') ? [
          <Button className="Button Button--link" onclick={this.showDebug.bind(this, error)}>Debug</Button>
        ] : undefined
      });

      try {
        options.errorHandler(error);
      } catch (error) {
        this.alerts.show(error.alert);
      }

      deferred.reject(error);
    });

    return deferred.promise;
  }

  /**
   * @param {RequestError} error
   * @private
   */
  showDebug(error) {
    this.alerts.dismiss(this.requestErrorAlert);

    this.modal.show(new RequestErrorModal({error}));
  }
}
