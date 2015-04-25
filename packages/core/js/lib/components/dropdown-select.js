import Component from 'flarum/component'
import icon from 'flarum/helpers/icon'
import listItems from 'flarum/helpers/list-items';

export default class DropdownSelect extends Component {
  view() {
    var activeItem = this.props.items.filter((item) => item.component.active && item.component.active(item.props))[0];
    var label = activeItem && activeItem.props.label;

    return m('div', {className: 'dropdown dropdown-select btn-group item-count-'+this.props.items.length+' '+this.props.className}, [
      m('a[href=javascript:;]', {className: 'dropdown-toggle '+(this.props.buttonClass || 'btn btn-default'), 'data-toggle': 'dropdown'}, [
        m('span.label', label), ' ',
        icon('sort icon-caret')
      ]),
      m('ul', {className: 'dropdown-menu '+this.props.menuClass}, listItems(this.props.items, true))
    ])
  }
}
