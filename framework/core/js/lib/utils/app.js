import ItemList from 'flarum/utils/item-list';

class App {
  constructor() {
    this.initializers = new ItemList();
    this.cache = {};
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));
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
