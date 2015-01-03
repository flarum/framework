import Ember from 'ember';

export default Ember.Component.extend({
    title: '',
    icon: '',
    className: '',
    action: null,
    divider: false,
    active: false,

    classNames: ['btn', 'btn-default'],

    tagName: 'a',
    attributeBindings: ['href'],
    classNameBindings: ['className'],
    href: '#',
    layout: Ember.Handlebars.compile('{{#if icon}}{{fa-icon icon class="fa-fw"}} {{/if}}<span>{{title}}</span>'),

    click: function(e) {
        e.preventDefault();
        // this.sendAction('action');
        this.get('action')();
    }
});