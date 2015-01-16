import Ember from 'ember';

export default Ember.Component.extend({
    label: '',
    icon: '',
    className: '',
    action: null,
    divider: false,
    active: false,

    classNames: [],

    tagName: 'a',
    attributeBindings: ['href', 'title'],
    classNameBindings: ['className'],
    href: '#',
    layout: Ember.Handlebars.compile('{{#if icon}}{{fa-icon icon class="fa-fw icon-glyph"}} {{/if}}<span>{{label}}</span>'),

    click: function(e) {
        e.preventDefault();
        var action = this.get('action');
        if (typeof action == 'string') {
            this.sendAction('action');
        } else if (typeof action == 'function') {
            action();
        }
    }
});