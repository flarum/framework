export default class PostTypes {
  postComponents = {};

  add(name, component) {
    this.postComponents[name] = component;

    return this;
  }

  extend(app, extension) {
    Object.assign(app.postComponents, this.postComponents);
  }
}