import Ember from 'ember';
import DS from 'ember-data';

var Discussion = DS.Model.extend({

	title: DS.attr('string'),

	slug: function() {
		return this.get('title').toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-');
	}.property('title'),
	
	canReply: DS.attr('boolean'),
	canEdit: DS.attr('boolean'),
	canDelete: DS.attr('boolean'),

	startTime: DS.attr('date'),
	startUser: DS.belongsTo('user'),
	startPost: DS.belongsTo('post'),

	lastTime: DS.attr('date'),
	lastUser: DS.belongsTo('user'),
	lastPost: DS.belongsTo('post'),
	lastPostNumber: DS.attr('number'),

	relevantPosts: DS.hasMany('post'),

	postsCount: DS.attr('number'),
	repliesCount: function() {
		return Math.max(0, this.get('postsCount') - 1);
	}.property('postsCount'),

	posts: DS.attr('string'),
	postIds: function() {
		return this.get('posts').split(',');
	}.property('posts'),

	readTime: DS.attr('date'),
	readNumber: DS.attr('number'),
	unreadCount: function() {
		return this.get('lastPostNumber') - this.get('readNumber');
	}.property('lastPostNumber', 'readNumber'),
	isUnread: Ember.computed.bool('unreadCount')
});

export default Discussion;
