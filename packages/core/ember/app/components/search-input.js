import Ember from 'ember';

export default Ember.Component.extend({
    classNames: ['search-input'],
    classNameBindings: ['active', 'value:clearable'],

    didInsertElement: function() {
        var self = this;
        this.$().find('input').on('keydown', function(e) {
            if (e.which == 27) {
                self.clear();
            }
        });
        this.$().find('.clear').on('mousedown', function(e) {
            e.preventDefault();
        }).on('click', function(e) {
            e.preventDefault();
            self.clear();
        })
    },

    clear: function() {
        this.set('value', '');
        this.sendAction('action', '');
        this.$().find('input').focus();
    },

    willDestroyElement: function() {
        this.$().find('input').off('keydown');
        this.$().find('.clear').off('mousedown click');
    },

    actions: {
        search: function() {
            this.sendAction('action', this.get('value'));
        }
    }
});
