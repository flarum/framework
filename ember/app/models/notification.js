import DS from 'ember-data';

export default DS.Model.extend({
  contentType: DS.attr('string'),
  subjectId: DS.attr('number'),
  content: DS.attr(),
  time: DS.attr('date'),
  isRead: DS.attr('boolean'),
  unreadCount: DS.attr('number'),
  additionalUnreadCount: Ember.computed('unreadCount', function() {
    return Math.max(0, this.get('unreadCount') - 1);
  }),

  decodedContent: Ember.computed('content', function() {
    return JSON.parse(this.get('content'));
  }),

  user: DS.belongsTo('user'),
  sender: DS.belongsTo('user'),
  subject: DS.belongsTo('subject', {polymorphic: true})
});
