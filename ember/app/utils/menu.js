import Ember from 'ember';

import NamedContainerView from './named-container-view';
import MenuItemSeparator from '../components/menu-item-separator';

export default NamedContainerView.extend({

    tagName: 'ul',

    active: null,

    i: 1,
    addSeparator: function(index) {
        var item = MenuItemSeparator;
        this.addItem('separator'+(this.i++), item, index);
    },

    activeChanged: function() {
        var active = this.get('active');
        if (typeof active != 'array') {
            active = [active];
        }

        var namedViews = this.get('namedViews');
        var view;
        for (var name in namedViews) {
            if (namedViews.hasOwnProperty(name) && (view = namedViews.get(name))) {
                view.set('active', active.indexOf(name) !== -1);
            }
        }
    }.observes('active')

});
