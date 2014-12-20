import Ember from 'ember';
import DS from 'ember-data';

export default DS.JsonApiSerializer.extend({
  normalize: function(type, hash, property) {
    var json = {};

    for (var prop in hash) {
      json[prop.camelize()] = hash[prop]; 
    }

    return this._super(type, json, property);
}
});
