export default class Model {
  constructor(data, store) {
    this.data = m.prop(data || {});
    this.freshness = new Date();
    this.exists = false;
    this.store = store;
  }

  id() {
    return this.data().id;
  }

  attribute(attribute) {
    return this.data().attributes[attribute];
  }

  pushData(newData) {
    var data = this.data();

    for (var i in newData) {
      if (i === 'relationships') {
        data[i] = data[i] || {};
        for (var j in newData[i]) {
          if (newData[i][j] instanceof Model) {
            newData[i][j] = {data: {type: newData[i][j].data().type, id: newData[i][j].data().id}};
          }
          data[i][j] = newData[i][j];
        }
      } else if (i === 'attributes') {
        data[i] = data[i] || {};
        for (var j in newData[i]) {
          data[i][j] = newData[i][j];
        }
      } else {
        data[i] = newData[i];
      }
    }

    this.freshness = new Date();
  }

  pushAttributes(attributes) {
    var data = {attributes};

    if (attributes.relationships) {
      data.relationships = attributes.relationships;
      delete attributes.relationships;
    }

    this.pushData(data);
  }

  save(attributes) {
    var data = {
      type: this.data().type,
      id: this.data().id,
      attributes
    };

    if (attributes.relationships) {
      data.relationships = {};

      for (var i in attributes.relationships) {
        var model = attributes.relationships[i];
        var relationshipData = model => {
          return {type: model.data().type, id: model.data().id};
        };
        if (model instanceof Array) {
          data.relationships[i] = {data: model.map(relationshipData)};
        } else {
          data.relationships[i] = {data: relationshipData(model)};
        }
      }

      delete attributes.relationships;
    }

    // clone the relevant parts of the model's old data so that we can revert
    // back if the save fails
    var oldData = {};
    var currentData = this.data();
    for (var i in data) {
      if (i === 'relationships') {
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
      method: this.exists ? 'PATCH' : 'POST',
      url: app.forum.attribute('apiUrl')+'/'+this.data().type+(this.exists ? '/'+this.data().id : ''),
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
      url: app.forum.attribute('apiUrl')+'/'+this.data().type+'/'+this.data().id,
      background: true,
      config: app.session.authorize.bind(app.session)
    }).then(() => this.exists = false);
  }

  static attribute(name, transform) {
    return function() {
      var data = this.data().attributes[name];
      return transform ? transform(data) : data;
    }
  }

  static hasOne(name) {
    return function() {
      var data = this.data();
      if (data.relationships) {
        var relationship = data.relationships[name];
        return relationship && app.store.getById(relationship.data.type, relationship.data.id);
      }
    }
  }

  static hasMany(name) {
    return function() {
      var data = this.data();
      if (data.relationships) {
        var relationship = this.data().relationships[name];
        return relationship && relationship.data.map(function(link) {
          return app.store.getById(link.type, link.id);
        });
      }
    }
  }

  static transformDate(data) {
    return data ? new Date(data) : null;
  }
}
