import Component from 'flarum/component';

export default class Alerts extends Component {
  constructor(props) {
    super(props);

    this.components = [];
  }

  view() {
    return m('div.alerts', this.components.map((component) => {
      component.props.ondismiss = this.dismiss.bind(this, component);
      return m('div.alert-wrapper', component);
    }));
  }

  show(component) {
    this.components.push(component);
    m.redraw();
  }

  dismiss(component) {
    var index = this.components.indexOf(component);
    if (index !== -1) {
      this.components.splice(index, 1);
    }
    m.redraw();
  }

  clear() {
    this.components = [];
    m.redraw();
  }
}
