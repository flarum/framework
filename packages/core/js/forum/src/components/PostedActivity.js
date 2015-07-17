import Activity from 'flarum/components/Activity';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';
import { truncate } from 'flarum/utils/string';

/**
 * The `PostedActivity` component displays an activity feed item for when a user
 * started or posted in a discussion.
 *
 * ### Props
 *
 * - All of the props for Activity
 */
export default class PostedActivity extends Activity {
  description() {
    const post = this.props.activity.subject();

    return app.trans(post.number() === 1 ? 'core.started_a_discussion' : 'core.posted_a_reply');
  }

  content() {
    const post = this.props.activity.subject();

    return (
      <a className="Activity-content PostedActivity-preview"
        href={app.route.post(post)}
        config={m.route}>
        <ul className="PostedActivity-header">
          {listItems(this.headerItems().toArray())}
        </ul>
        <div className="PostedActivity-body">
          {m.trust(truncate(post.contentPlain(), 200))}
        </div>
      </a>
    );
  }

  /**
   * Build an item list for the header of the post preview.
   *
   * @return {[type]}
   */
  headerItems() {
    const items = new ItemList();

    items.add('title', <h3>{this.props.activity.subject().discussion().title()}</h3>);

    return items;
  }
}
