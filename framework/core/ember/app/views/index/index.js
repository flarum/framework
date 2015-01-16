import Ember from 'ember';

export default Ember.View.extend({

    didInsertElement: function() {
        this.updateTitle();
    },

    updateTitle: function() {
        var q = this.get('controller.searchQuery');
        this.get('controller.controllers.application').set('pageTitle', q ? '"'+q+'"' : '');
    }.observes('controller.searchQuery')

});
