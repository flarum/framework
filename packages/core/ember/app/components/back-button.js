import Ember from 'ember';

export default Ember.Component.extend({
	classNames: ['back-button'],
	classNameBindings: ['active'],
	active: Ember.computed.or('target.paneShowing', 'target.panePinned'),
 
	mouseEnter: function() {
        this.get('target').send('showPane');
    },

    mouseLeave: function() {
        this.get('target').send('hidePane');
    },

    actions: {
    	back: function() {
    		this.get('target').send('transitionFromBackButton');
            this.set('target', null);
    	},
    	togglePinned: function() {
            this.get('target').send('togglePinned');
    	}
    }

});
