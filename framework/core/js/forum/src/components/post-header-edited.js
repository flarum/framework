import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/human-time';

/**
  Component for the edited pencil icon in a post header. Shows a tooltip on
  hover which details who edited the post and when.
 */
export default class PostHeaderEdited extends Component {
  view() {
    var post = this.props.post;

    var title = 'Edited '+(post.editUser() ? 'by '+post.editUser().username()+' ' : '')+humanTime(post.editTime());

    return m('span.post-edited', {
      title: title,
      config: (element) => $(element).tooltip()
    }, icon('pencil'));
  }
}
