/*! 
 * ember-json-api
 * Built on 2014-07-03
 * http://github.com/daliwali/ember-json-api
 * Copyright (c) 2014 Dali Zheng
 */
(function() {
"use strict";
var get = Ember.get;
var isNone = Ember.isNone;

DS.JsonApiSerializer = DS.RESTSerializer.extend({
  /**
   * Patch the extractSingle method, since there are no singular records
   */
  extractSingle: function(store, primaryType, payload, recordId) {
    var primaryTypeName;
    if (this.keyForAttribute) {
      primaryTypeName = this.keyForAttribute(primaryType.typeKey);
    } else {
      primaryTypeName = primaryType.typeKey;
    }

    var json = {};

    for (var key in payload) {
      var typeName = Ember.String.singularize(key);
      if (typeName === primaryTypeName &&
          Ember.isArray(payload[key])) {
        json[typeName] = payload[key][0];
      } else {
        json[key] = payload[key];
      }
    }
    return this._super(store, primaryType, json, recordId);
  },

  extractArray: function(store, primaryType, payload) {
    var primaryTypeName;
    if (this.keyForAttribute) {
      primaryTypeName = this.keyForAttribute(primaryType.typeKey);
    } else {
      primaryTypeName = primaryType.typeKey;
    }

    for (var key in payload) {
      var typeName = Ember.String.singularize(key);
      if (typeName === primaryTypeName &&
          ! Ember.isArray(payload[key])) {
        payload[key] = [payload[key]];
      }
    }
    
    return this._super(store, primaryType, payload);
  },

  /**
   * Flatten links
   */
  normalize: function(type, hash, prop) {
    var json = {};
    for (var key in hash) {
      if (key !== 'links') {
        json[key] = hash[key];
      } else if (typeof hash[key] === 'object') {
        for (var link in hash[key]) {
          json[link] = hash[key][link];
        }
      }
    }
    return this._super(type, json, prop);
  },

  /**
   * Extract top-level "meta" & "links" before normalizing.
   */
  normalizePayload: function(payload) {
    // if (payload.meta) {
    //   this.extractMeta(payload.meta);
    //   delete payload.meta;
    // }
    if (payload.links) {
      this.extractLinks(payload.links);
      delete payload.links;
    }
    if (payload.linked) {
      this.extractLinked(payload.linked);
      delete payload.linked;
    }
    return payload;
  },

  /**
   * Extract top-level "linked" containing associated objects
   */
  extractLinked: function(linked) {
    var link, values, value, relation;
    var store = get(this, 'store');

    for (link in linked) {
      values = linked[link];
      for (var i = values.length - 1; i >= 0; i--) {
        value = values[i];

        if (value.links) {
          for (relation in value.links) {
            value[relation] = value.links[relation];
          }
          delete value.links;
        }
      }
    }
    store.pushPayload(linked);
  },

  /**
   * Override this method to parse the top-level "meta" object per type.
   */
  // extractMeta: function(meta) {
  //   console.log(meta);
  //   // store.metaForType(type, payload.meta);
  // },

  /**
   * Parse the top-level "links" object.
   */
  extractLinks: function(links) {
    var link, key, value, route;
    var extracted = [], linkEntry, linkKey;

    for (link in links) {
      key = link.split('.').pop();
      value = links[link];
      if (typeof value === 'string') {
        route = value;
      } else {
        key = value.type || key;
        route = value.href;
      }

      // strip base url
      if (route.substr(0, 4).toLowerCase() === 'http') {
        route = route.split('//').pop().split('/').slice(1).join('/');
      }

      // strip prefix slash
      if (route.charAt(0) === '/') {
        route = route.substr(1);
      }
      linkEntry = { };
      linkKey = Ember.String.singularize(key);
      linkEntry[linkKey] = route;
      extracted.push(linkEntry);
      DS._routes[linkKey] = route;
    }

    return extracted;
  },

  // SERIALIZATION

  /**
   * Use "links" key, remove support for polymorphic type
   */
  serializeBelongsTo: function(record, json, relationship) {
    var key = relationship.key;
    var belongsTo = get(record, key);

    if (isNone(belongsTo)) return;

    json.links = json.links || {};
    json.links[key] = get(belongsTo, 'id');
  },

  /**
   * Use "links" key
   */
  serializeHasMany: function(record, json, relationship) {
    var key = relationship.key;

    var relationshipType = DS.RelationshipChange.determineRelationshipType(record.constructor, relationship);

    if (relationshipType === 'manyToNone' ||
        relationshipType === 'manyToMany') {
      json.links = json.links || {};
      json.links[key] = get(record, key).mapBy('id');
    }
  }
});

}).call(this);

(function() {
"use strict";
var get = Ember.get;

/**
 * Keep a record of routes to resources by type.
 */

// null prototype in es5 browsers wont allow collisions with things on the
// global Object.prototype.
DS._routes = Ember.create(null);

DS.JsonApiAdapter = DS.RESTAdapter.extend({
  defaultSerializer: 'DS/jsonApi',
  /**
   * Look up routes based on top-level links.
   */
  buildURL: function(typeName, id) {
    // TODO: this basically only works in the simplest of scenarios
    var route = DS._routes[typeName];
    if (!!route) {
      var url = [];
      var host = get(this, 'host');
      var prefix = this.urlPrefix();
      var param = /\{(.*?)\}/g;

      if (id) {
        if (param.test(route)) {
          url.push(route.replace(param, id));
        } else {
          url.push(route, id);
        }
      } else {
        url.push(route.replace(param, ''));
      }

      if (prefix) { url.unshift(prefix); }

      url = url.join('/');
      if (!host && url) { url = '/' + url; }

      return url;
    }

    return this._super(typeName, id);
  },

  /**
   * Fix query URL.
   */
  findMany: function(store, type, ids, owner) {
    return this.ajax(this.buildURL(type.typeKey, ids.join(',')), 'GET');
  },

  findQuery: function(store, type, query) {
    var ids = null;
    if (query.ids) {
      ids = query.ids.join(',');
      delete query.ids;
    }
    return this.ajax(this.buildURL(type.typeKey, ids), 'GET', {data: query});
  },

  /**
   * Cast individual record to array,
   * and match the root key to the route
   */
  createRecord: function(store, type, record) {
    var data = {};

    data[this.pathForType(type.typeKey)] = [
      store.serializerFor(type.typeKey).serialize(record, {
        includeId: true
      })
    ];

    return this.ajax(this.buildURL(type.typeKey), 'POST', {
        data: data
      });
  },

  /**
   * Cast individual record to array,
   * and match the root key to the route
   */
  updateRecord: function(store, type, record) {
    var data = {};
    data[this.pathForType(type.typeKey)] = [
      store.serializerFor(type.typeKey).serialize(record)
    ];

    var id = get(record, 'id');

    return this.ajax(this.buildURL(type.typeKey, id), 'PUT', {
        data: data
      });
  },

  _tryParseErrorResponse:  function(responseText) {
    try {
      return Ember.$.parseJSON(responseText);
    } catch(e) {
      return "Something went wrong";
    }
  },

  ajaxError: function(jqXHR) {
    var error = this._super(jqXHR);
    var response;

    if (jqXHR && typeof jqXHR === 'object') {
      response = this._tryParseErrorResponse(jqXHR.responseText);
      var errors = {};

      if (response &&
          typeof response === 'object' &&
            response.errors !== undefined) {

        Ember.A(Ember.keys(response.errors)).forEach(function(key) {
          errors[Ember.String.camelize(key)] = response.errors[key];
        });
      }

      if (jqXHR.status === 422) {
        return new DS.InvalidError(errors);
      } else{
        return new ServerError(jqXHR.status, response, jqXHR);
      }
    } else {
      return error;
    }
  },
  /**
    Underscores the JSON root keys when serializing.

    @method serializeIntoHash
    @param {Object} hash
    @param {subclass of DS.Model} type
    @param {DS.Model} record
    @param {Object} options
    */
  serializeIntoHash: function(data, type, record, options) {
    var root = underscore(decamelize(type.typeKey));
    data[root] = this.serialize(record, options);
  }
});

function ServerError(status, message, xhr) {
  this.status = status;
  this.message = message;
  this.xhr = xhr;

  this.stack = new Error().stack;
}

ServerError.prototype = Ember.create(Error.prototype);
ServerError.constructor = ServerError;

DS.JsonApiAdapter.ServerError = ServerError;

}).call(this);
