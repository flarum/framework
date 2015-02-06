import Ember from 'ember';

export default Ember.View.extend({

    didInsertElement: function() {
        this.updateTitle();
        $(window).scrollTop(this.get('controller.scrollTop'));
    },

    willDestroyElement: function() {
    	this.set('controller.scrollTop', $(window).scrollTop());
    },

    updateTitle: function() {
        var q = this.get('controller.searchQuery');
        this.get('controller.controllers.application').set('pageTitle', q ? '"'+q+'"' : '');
    }.observes('controller.searchQuery')

});
