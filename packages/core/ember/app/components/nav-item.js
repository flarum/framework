import Ember from 'ember';

export default Ember.Component.extend({
	icon: '',
	title: '',
	action: null,
	badge: '',
	badgeAction: null,
	// active: false,

	tagName: 'li',
	classNameBindings: ['active'],
    active: function() {
        return this.get('childViews').anyBy('active');
    }.property('childViews.@each.active'),

	layout: function() {
        return Ember.Handlebars.compile('<a href="#" class="count" {{action "badge"}}>{{badge}}</a>\
            {{#link-to '+this.get('linkTo')+'}}'+this.get('iconTemplate')+'{{title}}{{/link-to}}');
    }.property('linkTo', 'iconTemplate'),

    iconTemplate: function() {
        return '{{fa-icon icon}}';
    }.property(),

    actions: {
        main: function() {
            this.get('action')();
        },
        badge: function() {
            this.get('badgeAction')();
        }
    }
});
