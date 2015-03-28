import DS from 'ember-data';

export default DS.Model.extend({
  notification: DS.belongsTo('notification')
});
