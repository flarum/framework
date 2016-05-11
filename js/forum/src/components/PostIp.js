import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

/**
 * The `PostIp` component displays a 'IP', and when clicked,
 * shows a dropdown containing the IP address.
 *
 * ### Props
 *
 * - `post`
 */
export default class PostIp extends Component {
  view() {
    const post = this.props.post;

    return (
      <div className="Dropdown PostIp">
        <a className="Dropdown-toggle" data-toggle="dropdown">
          {app.translator.trans('core.forum.post.ip')}
        </a>

        <div className="Dropdown-menu dropdown-menu">
          <span className="PostIp-number">{(post.data.attributes.userIp ? post.data.attributes.userIp : app.translator.trans('core.forum.post.noIp'))}</span>
        </div>
      </div>
    );
  }
}
