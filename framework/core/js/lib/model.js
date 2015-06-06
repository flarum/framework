export default class Model {
  constructor(data, store) {
    this.data = m.prop(data || {});
    this.freshness = new Date();
    this.exists = false;
    this.store = store;
  }

  pushData(newData) {
    var data = this.data();

    for (var i in newData) {
      if (i === 'links') {
        data[i] = data[i] || {};
        for (var j in newData[i]) {
          if (newData[i][j] instanceof Model) {
            newData[i][j] = {linkage: {type: newData[i][j].data().type, id: newData[i][j].data().id}};
          }
          data[i][j] = newData[i][j];
        }
      } else {
        data[i] = newData[i];
      }
    }

    this.freshness = new Date();
  }

  save(data) {
    if (data.links) {
      for (var i in data.links) {
        var model = data.links[i];
        data.links[i] = {linkage: {type: model.data().type, id: model.data().id}};
      }
    }

    // clone the relevant parts of the model's old data so that we can revert
    // back if the save fails
    var oldData = {};
    var currentData = this.data();
    for (var i in data) {
      if (i === 'links') {
        oldData[i] = oldData[i] || {};
        for (var j in currentData[i]) {
          oldData[i][j] = currentData[i][j];
        }
      } else {
        oldData[i] = currentData[i];
      }
    }

    this.pushData(data);

    return app.request({
      method: this.exists ? 'PUT' : 'POST',
      url: app.config['api_url']+'/'+this.data().type+(this.exists ? '/'+this.data().id : ''),
      data: {data},
      background: true,
      config: app.session.authorize.bind(app.session)
    }).then(payload => {
      this.store.data[payload.data.type][payload.data.id] = this;
      return this.store.pushPayload(payload);
    }, response => {
      this.pushData(oldData);
      throw response;
    });
  }

  delete() {
    if (!this.exists) { return; }

    return app.request({
      method: 'DELETE',
      url: app.config['api_url']+'/'+this.data().type+'/'+this.data().id,
      background: true,
      config: app.session.authorize.bind(app.session)
    }).then(() => this.exists = false);
  }

  static prop(name, transform) {
    return function() {
      var data = this.data()[name];
      return transform ? transform(data) : data;
    }
  }

  static one(name) {
    return function() {
      var data = this.data();
      if (data.links) {
        var link = data.links[name];
        return link && app.store.getById(link.linkage.type, link.linkage.id);
      }
    }
  }

  static many(name) {
    return function() {
      var data = this.data();
      if (data.links) {
        var link = this.data().links[name];
        return link && link.linkage.map(function(link) {
          return app.store.getById(link.type, link.id)
        });
      }
    }
  }

  static date(data) {
    return data ? new Date(data) : null;
  }
}
