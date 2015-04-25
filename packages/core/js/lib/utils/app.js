import ItemList from 'flarum/utils/item-list';

class App {
  constructor() {
    this.initializers = new ItemList();
    this.cache = {};
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));
  }

  route(name, args, queryParams) {
    var queryString = m.route.buildQueryString(queryParams);
    return this.routes[name][0].replace(/:([^\/]+)/g, function(m, t) {
      return typeof args[t] === 'function' ? args[t]() : args[t];
    }) + (queryString ? '?'+queryString : '');
  }
}

export default App;
