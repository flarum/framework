import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/humanTime';
import extractText from 'flarum/utils/extractText';

/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 *
 * ### Props
 *
 * - `post`
 */
export default class PostEdited extends Component {
  view() {
    const post = this.props.post;
    const editUser = post.editUser();
    const title = extractText(app.trans('core.post_edited', {user: editUser, ago: humanTime(post.editTime())}));

    return (
      <span className="PostEdited" title={title}>{icon('pencil')}</span>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    this.$().tooltip();
  }
}
