import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import copyToClipboard from 'flarum/helpers/copyToClipboard';
import Alert from 'flarum/components/Alert';

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
    const userIp = post.data.attributes.userIp;

    return (
      <span className="PostIp" onclick={() => this.onClick(userIp)} title={userIp}>{icon('bullseye')}</span>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    this.$().tooltip();
  }
  
  onClick(text) {
    const attempt = copyToClipboard(text);
    if (attempt) {
      app.alerts.show(
        alert = new Alert({
          type: 'success',
          message: app.translator.trans('core.forum.post.successfulIpCopy'),
          children: app.translator.trans('core.forum.post.successfulIpCopy')
        })
      );
    } else {
      app.alerts.show(
        alert = new Alert({
          type: 'success',
          message: text,
          children: [<input class="FormControl IpAlertInput" value={text}></input>]
        })
      );
    }
  }
}
