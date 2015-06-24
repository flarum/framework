import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/list-items';

export default class DropdownButton extends Component {
  view() {
    return m('div', {className: 'dropdown btn-group '+(this.props.items ? 'item-count-'+this.props.items.length : '')+' '+(this.props.className || '')}, [
      m('a[href=javascript:;]', {
        className: 'dropdown-toggle '+(this.props.buttonClass || 'btn btn-default'),
        'data-toggle': 'dropdown',
        onclick: this.props.buttonClick
      }, this.props.buttonContent || [
        icon((this.props.icon || 'ellipsis-v')+' icon-glyph icon'),
        m('span.label', this.props.label || 'Controls'),
        icon('caret-down icon-caret')
      ]),
      m(this.props.menuContent ? 'div' : 'ul', {className: 'dropdown-menu '+(this.props.menuClass || '')}, this.props.menuContent || listItems(this.props.items))
    ]);
  }
}
