import ItemList from 'flarum/utils/item-list';
import Alert from 'flarum/components/alert';
import ServerError from 'flarum/utils/server-error';

class App {
  constructor() {
    this.initializers = new ItemList();
    this.cache = {};
    this.serverError = null;
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));
  }

  setTitle(title) {
    document.title = (title ? title+' - ' : '')+this.config['forum_title'];
  }

  request(options) {
    var extract = options.extract;
    options.extract = function(xhr, xhrOptions) {
      if (xhr.status === 500) {
        throw new ServerError;
      }
      return extract ? extract(xhr.responseText) : (xhr.responseText.length === 0 ? null : xhr.responseText);
    };

    return m.request(options).then(response => {
      this.alerts.dismiss(this.serverError);
      return response;
    }, response => {
      this.alerts.dismiss(this.serverError);
      if (response instanceof ServerError) {
        this.alerts.show(this.serverError = new Alert({ type: 'warning', message: 'Oops! Something went wrong on the server. Please try again.' }))
      }
      throw response;
    });
  }

  handleApiErrors(response) {
    this.alerts.clear();

    response.errors.forEach(error =>
      this.alerts.show(new Alert({ type: 'warning', message: error.detail }))
    );
  }

  route(name, params) {
    var url = this.routes[name][0].replace(/:([^\/]+)/g, function(m, t) {
      var value = params[t];
      delete params[t];
      return value;
    });
    var queryString = m.route.buildQueryString(params);
    return url+(queryString ? '?'+queryString : '');
  }
}

export default App;
