import Ember from 'ember';

export default Ember.View.extend({

    classNames: ['search-nav'],
    templateName: 'discussions-nav',

    type: 'discussions',

    mouseEnter: function() {
        clearTimeout(this.get('controller.paneTimeout'));
        this.set('controller.paneShowing', true);
    },

    mouseLeave: function() {
        var view = this;
        this.set('controller.paneTimeout', setTimeout(function() {
            view.set('controller.paneShowing', false);
        }, 500));
    }

});
