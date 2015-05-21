import Component from 'flarum/component';
import YesNoInput from 'flarum/components/yesno-input';
import icon from 'flarum/helpers/icon';

export default class NotificationGrid extends Component {
  constructor(props) {
    super(props);

    this.methods = [
      { name: 'alert', icon: 'bell', label: 'Alert' },
      { name: 'email', icon: 'envelope-o', label: 'Email' }
    ];

    this.inputs = {};
    this.props.types.forEach(type => {
      this.methods.forEach(method => {
        var key = this.key(type.name, method.name);
        var preference = this.props.user.preferences()[key];
        this.inputs[key] = new YesNoInput({
          state: !!preference,
          disabled: typeof preference == 'undefined',
          onchange: () => this.toggle([key])
        });
      });
    });
  }

  key(type, method) {
    return 'notify_'+type+'_'+method;
  }

  view() {
    return m('div.notification-grid', {config: this.onload.bind(this)}, [
      m('table', [
        m('thead', [
          m('tr', [
            m('td'),
            this.methods.map(method => m('th.toggle-group', {onclick: this.toggleMethod.bind(this, method.name)}, [icon(method.icon), ' ', method.label]))
          ])
        ]),
        m('tbody', [
          this.props.types.map(type => m('tr', [
            m('td.toggle-group', {onclick: this.toggleType.bind(this, type.name)}, type.label),
            this.methods.map(method => {
              var key = this.key(type.name, method.name);
              return m('td.yesno-cell', this.inputs[key].view());
            })
          ]))
        ])
      ])
    ]);
  }

  onload(element, isInitialized) {
    if (isInitialized) { return; }

    this.element(element);

    var self = this;
    this.$('thead .toggle-group').bind('mouseenter mouseleave', function(e) {
      var i = parseInt($(this).index()) + 1;
      self.$('table').find('td:nth-child('+i+')').toggleClass('highlighted', e.type === 'mouseenter');
    });
    this.$('tbody .toggle-group').bind('mouseenter mouseleave', function(e) {
      $(this).parent().find('td').toggleClass('highlighted', e.type === 'mouseenter');
    });
  }

  toggle(keys) {
    var user = this.props.user;
    var preferences = user.preferences();
    var enabled = !preferences[keys[0]];
    keys.forEach(key => {
      var control = this.inputs[key];
      control.loading(true);
      preferences[key] = control.props.state = enabled;
    });
    m.redraw();

    user.save({preferences}).then(() => {
      keys.forEach(key => this.inputs[key].loading(false));
      m.redraw();
    });
  }

  toggleMethod(method) {
    var keys = this.props.types.map(type => this.key(type.name, method)).filter(key => !this.inputs[key].props.disabled);
    this.toggle(keys);
  }

  toggleType(type) {
    var keys = this.methods.map(method => this.key(type, method.name)).filter(key => !this.inputs[key].props.disabled);
    this.toggle(keys);
  }
}
