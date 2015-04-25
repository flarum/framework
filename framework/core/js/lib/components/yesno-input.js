import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';
import classList from 'flarum/utils/class-list';
import icon from 'flarum/helpers/icon';

export default class YesNoInput extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
  }

  view() {
    return m('label.yesno-control', [
      m('input[type=checkbox]', {
        checked: this.props.state,
        disabled: this.props.disabled,
        onchange: m.withAttr('checked', this.onchange.bind(this))
      }),
      m('div.yesno', {className: classList({
        loading: this.loading(),
        disabled: this.props.disabled,
        state: this.props.state ? 'yes' : 'no'
      })}, [
        this.loading()
          ? LoadingIndicator.component({size: 'tiny'})
          : icon(this.props.state ? 'check' : 'times')
      ])
    ]);
  }

  onchange(checked) {
    this.props.onchange && this.props.onchange(checked, this);
  }
}
