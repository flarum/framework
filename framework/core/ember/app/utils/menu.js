import Ember from 'ember';

import NamedContainerView from './named-container-view';
import MenuItemSeparator from '../components/menu-item-separator';

export default NamedContainerView.extend({

    i: 1,
    addSeparator: function(index) {
        var item = MenuItemSeparator;
        this.addItem('separator'+(this.i++), item, index);
    }

});
