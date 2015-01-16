import Ember from 'ember';

export default Ember.View.extend({

    classNames: ['composer'],

    // classNameBindings: ['controller.showing:showing'],

    // showingChanged: function() {
    //     if (this.$()) {
    //         var view = this;
    //         this.$().animate({bottom: this.get('controller.showing') ? 20 : -this.$().height()}, 'fast', function() {
    //             if (view.get('controller.showing')) {
    //                 $(this).find('textarea').focus();
    //             }
    //         });
    //         $('#body').animate({marginBottom: this.get('controller.showing') ? this.$().height() + 20 : 0}, 'fast');
    //     }
    // }.observes('controller.showing'),

    // panePinnedChanged: function() {
    //     if (this.$()) {
    //         var discussions = this.get('controller.controllers.discussions');
    //         var $this = this.$();
    //         Ember.run.scheduleOnce('afterRender', function() {
    //             var discussion = $('.discussion-pane');
    //             var width = discussion.length ? discussion.offset().left : $('#body').offset().left;
    //             $this.css('left', width);
    //         });
    //     }
    // }.observes('controller.controllers.discussions.paned', 'controller.controllers.discussions.panePinned'),

    didInsertElement: function() {
        // this.showingChanged();
        // this.panePinnedChanged();
    }

});
