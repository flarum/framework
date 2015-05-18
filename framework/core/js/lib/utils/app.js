import ItemList from 'flarum/utils/item-list';
import Alert from 'flarum/components/alert';

class App {
  constructor() {
    this.initializers = new ItemList();
    this.cache = {};
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));
  }

  setTitle(title) {
    document.title = (title ? title+' - ' : '')+this.config['forum_title'];
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
