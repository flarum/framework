import Ember from 'ember';

export default Ember.Component.extend({
    items: null, // NamedContainerView/Menu
    layoutName: 'components/menu-split',
    show: 1,

    visibleItems: function() {
        return this.get('items').slice(0, this.get('show'));
    }.property('items'), 

    hiddenItems: function() {
        return this.get('items').slice(this.get('show'));
    }.property('items'), 
});
