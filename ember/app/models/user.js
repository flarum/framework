import DS from 'ember-data';

export default DS.Model.extend({

	username: DS.attr('string'),
	avatarUrl: DS.attr('string'),
	joinTime: DS.attr('date'),
	lastSeenTime: DS.attr('date'),
	discussionsCount: DS.attr('number'),
	postsCount: DS.attr('number'),

	canEdit: DS.attr('boolean'),
	canDelete: DS.attr('boolean'),

	groups: DS.hasMany('group'),

    avatarNumber: function() {
        return Math.random() > 0.3 ? Math.floor(Math.random() * 19) + 1 : null;
    }.property()
});
