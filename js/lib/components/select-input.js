import Component from 'flarum/component'
import icon from 'flarum/helpers/icon';

export default class SelectInput extends Component {
  view(ctrl) {
    return m('span.select-input', [
      m('select.form-control', {onchange: m.withAttr('value', this.props.onchange.bind(ctrl)), value: this.props.value}, [
        this.props.options.map(function(option) { return m('option', {value: option.key}, option.value) })
      ]),
      icon('sort')
    ])
  }
}
