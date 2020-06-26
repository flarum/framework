export default class Routes {
  type;
  attributes = [];
  hasOnes = [];
  hasManys = [];

  constructor(type, model = null) {
    this.type = type;
    this.model = model;
  }

  attribute(name) {
    this.attributes.push(name);

    return this;
  }

  hasOne(type) {
    this.hasOnes.push(type);

    return this;
  }

  hasMany(type) {
    this.hasManys.push(type);

    return this;
  }

  extend(app, extension) {
    if (this.model) {
      app.store.models[this.type] = this.model;
    }

    const model = app.store.models[this.type];

    this.attributes.forEach((name) => (model.prototype[name] = model.attribute(name)));
    this.hasOnes.forEach((name) => (model.prototype[name] = model.hasOne(name)));
    this.hasManys.forEach((name) => (model.prototype[name] = model.hasMany(name)));
  }
}
