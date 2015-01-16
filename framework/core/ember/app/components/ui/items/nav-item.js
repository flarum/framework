import Ember from 'ember';

export default Ember.Component.extend({
	icon: '',
	label: '',
	action: null,
	badge: '',

	tagName: 'li',
	classNameBindings: ['active'],
    active: function() {
        return !! this.get('childViews').anyBy('active');
    }.property('childViews.@each.active'),

    // init: function() {
    //     var params = this.params;
    //     if (params[params.length - 1].queryParams) {
    //         this.queryParamsObject = {values: params.pop().queryParams};
    //     }

    //     this._super();
    // },

	layout: function() {
        return Ember.Handlebars.compile('{{#link-to '+this.get('linkTo')+'}}'+this.get('iconTemplate')+' {{label}} <span class="count">{{badge}}</span>{{/link-to}}');
    }.property('linkTo', 'iconTemplate'),

    iconTemplate: function() {
        return '{{fa-icon icon}}';
    }.property(),

    actions: {
        main: function() {
            this.get('action')();
        }
    }
});
