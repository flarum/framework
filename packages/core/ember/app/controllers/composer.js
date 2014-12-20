import Ember from 'ember';

export default Ember.Controller.extend({

    needs: ['discussions'],

    showing: false,

    title: 'Replying to <em>Some Discussion Title</em>',

    actions: {
        close: function() {
            this.set('showing', false);
        }
    }

});
