export default class Routes {
  routes = {};

  add(name, path, component) {
    this.routes[name] = { path, component };

    return this;
  }

  extend(app, extension) {
    Object.assign(app.routes, this.routes);
  }
}
