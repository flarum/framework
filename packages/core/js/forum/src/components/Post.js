import Component from 'flarum/Component';
import SubtreeRetainer from 'flarum/utils/SubtreeRetainer';
import Dropdown from 'flarum/components/Dropdown';
import PostControls from 'flarum/utils/PostControls';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 *
 * ### Props
 *
 * - `post`
 *
 * @abstract
 */
export default class Post extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Set up a subtree retainer so that the post will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    this.subtree = new SubtreeRetainer(
      () => this.props.post.freshness,
      () => {
        const user = this.props.post.user();
        return user && user.freshness;
      }
    );
  }

  view() {
    const attrs = this.attrs();

    attrs.className = 'Post ' + (attrs.className || '');

    return (
      <article {...attrs}>
        {this.subtree.retain() || (() => {
          const controls = PostControls.controls(this.props.post, this).toArray();

          return (
            <div>
              {this.content()}
              <aside className="Post-actions">
                <ul>
                  {listItems(this.actionItems().toArray())}
                  {controls.length ? <li>
                    <Dropdown
                      className="Post-controls"
                      buttonClassName="Button Button--icon Button--flat"
                      menuClassName="Dropdown-menu--right"
                      icon="ellipsis-h">
                      {controls}
                    </Dropdown>
                  </li> : ''}
                </ul>
              </aside>
            </div>
          );
        })()}
      </article>
    );
  }

  /**
   * Get attributes for the post element.
   *
   * @return {Object}
   */
  attrs() {
    return {};
  }

  /**
   * Get the post's content.
   *
   * @return {Object}
   */
  content() {
    return '';
  }

  /**
   * Build an item list for the post's actions.
   *
   * @return {ItemList}
   */
  actionItems() {
    return new ItemList();
  }
}
