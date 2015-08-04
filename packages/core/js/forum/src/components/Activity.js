import Component from 'flarum/Component';
import humanTime from 'flarum/helpers/humanTime';
import avatar from 'flarum/helpers/avatar';

/**
 * The `Activity` component represents a piece of activity of a user's activity
 * feed. Subclasses should implement the `description` and `content` methods.
 *
 * ### Props
 *
 * - `activity`
 *
 * @abstract
 */
export default class Activity extends Component {
  view() {
    const activity = this.props.activity;

    return (
      <div className="Activity">
        {avatar(this.user(), {className: 'Activity-avatar'})}

        <div className="Activity-header">
          <strong className="Activity-description">{this.description()}</strong>
          {humanTime(this.time())}
        </div>

        {this.content()}
      </div>
    );
  }

  /**
   * Get the user whose avatar should be displayed.
   *
   * @return {User}
   * @abstract
   */
  user() {
  }

  /**
   * Get the time of the activity.
   *
   * @return {Date}
   * @abstract
   */
  time() {
  }

  /**
   * Get the description of the activity.
   *
   * @return {VirtualElement}
   */
  description() {
  }

  /**
   * Get the content to show below the activity description.
   *
   * @return {VirtualElement}
   */
  content() {
  }
}
