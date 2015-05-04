import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/list-items';
import ActionButton from 'flarum/components/action-button';

/**
  Given a list of items, this component displays a split button: the left side
  is the first item in the list, while the right side is a dropdown-toggle
  which shows a dropdown menu containing all of the items.
 */
export default class DropdownSplit extends Component {
  view() {
    var firstItem = this.props.items[0];
    var items = listItems(this.props.items);

    var buttonProps = {};
    for (var i in firstItem.props) {
      buttonProps[i] = firstItem.props[i];
    }
    buttonProps.className = (buttonProps.className || '')+' '+(this.props.buttonClass || 'btn btn-default');

    return m('div', {className: 'dropdown dropdown-split btn-group item-count-'+(items.length)+' '+this.props.className}, [
      ActionButton.component(buttonProps),
      m('a[href=javascript:;]', {className: 'dropdown-toggle '+this.props.buttonClass, 'data-toggle': 'dropdown'}, [
        icon('caret-down icon-caret'),
        icon((this.props.icon || 'ellipsis-v')+' icon'),
      ]),
      m('ul', {className: 'dropdown-menu '+(this.props.menuClass || 'pull-right')}, items)
    ])
  }
}
