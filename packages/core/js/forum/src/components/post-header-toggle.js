import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';

/**
  Component for the toggle button in a post header. Toggles the
  `parent.revealContent` property when clicked. Only displays if the supplied
  post is not hidden.
 */
export default class PostHeaderToggle extends Component {
  view() {
    return m('a.btn.btn-default.btn-more[href=javascript:;]', {onclick: this.props.toggle}, icon('ellipsis-h'));
  }
}
