import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';

/**
  The back/pin button group in the top-left corner of Flarum's interface.
 */
export default class BackButton extends Component {
  view() {
    var history = app.history;
    var pane = app.pane;

    return m('div.back-button', {
      className: this.props.className || '',
      onmouseenter: pane && pane.show.bind(pane),
      onmouseleave: pane && pane.onmouseleave.bind(pane),
      config: this.onload.bind(this)
    }, history.canGoBack() ? m('div.btn-group', [
      m('button.btn.btn-default.btn-icon.back', {onclick: history.back.bind(history)}, icon('chevron-left icon')),
      pane && pane.active ? m('button.btn.btn-default.btn-icon.pin'+(pane.pinned ? '.active' : ''), {onclick: pane.togglePinned.bind(pane)}, icon('thumb-tack icon')) : '',
    ]) : (this.props.drawer ? [
      m('button.btn.btn-default.btn-icon.drawer-toggle', {
        onclick: app.drawer.toggle.bind(app.drawer),
        className: app.session.user() && app.session.user().unreadNotificationsCount() ? 'unread-notifications' : ''
      }, icon('reorder icon'))
    ] : ''));
  }

  onload(element, isInitialized, context) {
    context.retain = true;
  }
}
