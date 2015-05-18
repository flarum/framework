import Component from 'flarum/component';
import ActionButton from 'flarum/components/action-button';
import listItems from 'flarum/helpers/list-items';

export default class Alert extends Component {
  view() {
    var attrs = {};
    for (var i in this.props) { attrs[i] = this.props[i]; }

    attrs.className = (attrs.className || '') + ' alert-'+attrs.type;
    delete attrs.type;

    var message = attrs.message;
    delete attrs.message;

    var controlItems = attrs.controls ? attrs.controls.slice() : [];
    delete attrs.controls;

    if (attrs.dismissible || attrs.dismissible === undefined) {
      controlItems.push(ActionButton.component({
        icon: 'times',
        className: 'btn btn-icon btn-link',
        onclick: attrs.ondismiss.bind(this)
      }));
    }
    delete attrs.dismissible;

    return m('div.alert', attrs, [
      m('span.alert-text', message),
      m('ul.alert-controls', listItems(controlItems))
    ]);
  }
}
