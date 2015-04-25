import Component from 'flarum/component';
import listItems from 'flarum/helpers/list-items';

export default class FieldSet extends Component {
  view() {
    return m('fieldset', {className: this.props.className}, [
      m('legend', this.props.label),
      m('ul', listItems(this.props.fields))
    ]);
  }
}
