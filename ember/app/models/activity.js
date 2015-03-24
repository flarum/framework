import DS from 'ember-data';

export default DS.Model.extend({
  contentType: DS.attr('string'),
  content: DS.attr(),
  time: DS.attr('date'),

  user: DS.belongsTo('user'),
  sender: DS.belongsTo('user'),
  post: DS.belongsTo('post')
});
