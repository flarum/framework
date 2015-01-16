import Ember from 'ember';

export default Ember.Controller.extend({

    needs: ['index'],

    user: Ember.Object.create({avatarNumber: 1}),

    showing: false,

    title: 'Replying to <em>Some Discussion Title</em>',

    actions: {
        close: function() {
            this.set('showing', false);
        }
    }

});
