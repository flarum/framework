import Ember from 'ember';

export default Ember.View.extend({
    
    title: function() {
        return this.get('controller.forumTitle');
    }.property('controller.forumTitle')

});
