import Ember from 'ember';

export default Ember.View.extend({

    didInsertElement: function() {
        this.updateTitle();
        var scrollTop = this.get('controller.scrollTop');
        $(window).scrollTop(scrollTop);

        var lastDiscussion = this.get('controller.lastDiscussion');
        if (lastDiscussion) {
            var $discussion = $('.index-area .discussion-summary[data-id='+lastDiscussion.get('id')+']');
            if ($discussion.length) {
                var indexTop = $('#header').outerHeight();
                var discussionTop = $discussion.offset().top;
                if (discussionTop < scrollTop + indexTop || discussionTop + $discussion.outerHeight() > scrollTop + $(window).height()) {
                    $(window).scrollTop(discussionTop - indexTop);
                }
            }
        }
    },

    willDestroyElement: function() {
    	this.set('controller.scrollTop', $(window).scrollTop());
    },

    updateTitle: function() {
        var q = this.get('controller.searchQuery');
        this.get('controller.controllers.application').set('pageTitle', q ? '"'+q+'"' : '');
    }.observes('controller.searchQuery')

});
