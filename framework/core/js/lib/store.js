export default class Store {
  constructor() {
    this.data = {}
    this.models = {}
  }

  pushPayload(payload) {
    payload.included && payload.included.map(this.pushObject.bind(this))
    var result = payload.data instanceof Array ? payload.data.map(this.pushObject.bind(this)) : this.pushObject(payload.data);
    result.payload = payload;
    return result;
  }

  pushObject(data) {
    if (!this.models[data.type]) { return; }
    var type = this.data[data.type] = this.data[data.type] || {};

    if (type[data.id]) {
      type[data.id].pushData(data);
    } else {
      type[data.id] = this.createRecord(data.type, data);
    }
    type[data.id].exists = true;
    return type[data.id];
  }

  find(type, id, query) {
    var endpoint = type
    var params = {}
    if (id instanceof Array) {
      endpoint += '?ids[]='+id.join('&ids[]=');
      params = query
    } else if (typeof id === 'object') {
      params = id
    } else if (id) {
      endpoint += '/'+id
      params = query
    }
    return m.request({
      method: 'GET',
      url: app.config['api_url']+'/'+endpoint,
      data: params,
      background: true,
      config: app.session.authorize.bind(app.session)
    }).then(this.pushPayload.bind(this));
  }

  getById(type, id) {
    return this.data[type] && this.data[type][id];
  }

  getBy(type, key, value) {
    return this.all(type).filter(model => model[key]() == value)[0];
  }

  all(type) {
    var data = this.data[type];
    return data ? Object.keys(data).map(id => data[id]) : [];
  }

  createRecord(type, data) {
    data = data || {};
    data.type = data.type || type;

    return new (this.models[type])(data, this);
  }
}
