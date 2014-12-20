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

	readNumber: DS.attr('number'),
	unreadCount: function() {
		return this.get('lastPostNumber') - this.get('readNumber');
	}.property('lastPostNumber', 'readNumber'),

	//--------------------------------
	// Prototype generated properties

	// category: function() {
	// 	var categories = [null, 'Announcements', 'General', 'Support', 'Feedback', 'Core', 'Plugins', 'Themes'];
	// 	return categories[Math.floor(Math.random() * categories.length)];
	// }.property(),
	category: DS.attr('string'),

	_recent: function() {
		var cutoff = new Date('September 19, 2014');
		return this.get('lastTime') > cutoff;
	}.property('lastTime'),

	unread: function() {
		return Math.round(Math.random() * (this.get('_recent') ? 0.8 : 0) * this.get('postsCount'));
	}.property(),

	// sticky: function() {
	// 	return Math.random() > (this.get('_recent') ? 0.95 : 0.99);
	// }.property(),
	sticky: DS.attr('boolean'),

	excerpt: function() {
		// return 'I want to get your thoughts on this one TV Addicts: what new show have you been getting into this year, and why?';
		// return 'Here\'s the near-final game list, in no particular order. The list may be subject to amendments, as we\'re still chasing up copies of some games.';
		// return 'Nominating for the Annual General Meeting is easy. Read this to find out how.'
		return 'There are many apps made with Ninetech in the Mac App Store. If you\'d like, take a moment to share your Nintech-made apps in this thread.';
	}.property(),

	locked: function() {
		return Math.random() > 0.95;
	}.property(),

	following: function() {
		return Math.random() > 0.95;
	}.property()
});

export default Discussion;
