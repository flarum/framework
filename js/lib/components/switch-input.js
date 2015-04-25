import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default class SwitchInput extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
  }

  view() {
    return m('div.checkbox.checkbox-switch', [
      m('label', [
        m('div.switch-control', [
          m('input[type=checkbox]', {
            checked: this.props.state,
            onchange: m.withAttr('checked', this.onchange.bind(this))
          }),
          m('div.switch', {className: this.loading() && 'loading'})
        ]),
        this.props.label, ' ',
        this.loading() ? LoadingIndicator.component({size: 'tiny'}) : ''
      ])
    ])
  }

  onchange(checked) {
    this.props.onchange && this.props.onchange(checked, this);
  }
}
