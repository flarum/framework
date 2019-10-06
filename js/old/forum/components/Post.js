import Component from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import Dropdown from '../../common/components/Dropdown';
import PostControls from '../utils/PostControls';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';

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
  init() {
    this.loading = false;

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
      },
      () => this.controlsOpen
    );
  }

  view() {
    const attrs = this.attrs();

    attrs.className = this.classes(attrs.className).join(' ');

    return (
      <article {...attrs}>
        {this.subtree.retain() ||
          (() => {
            const controls = PostControls.controls(this.props.post, this).toArray();

            return (
              <div>
                {this.content()}
                <aside className="Post-actions">
                  <ul>
                    {listItems(this.actionItems().toArray())}
                    {controls.length ? (
                      <li>
                        <Dropdown
                          className="Post-controls"
                          buttonClassName="Button Button--icon Button--flat"
                          menuClassName="Dropdown-menu--right"
                          icon="fas fa-ellipsis-h"
                          onshow={() => this.$('.Post-actions').addClass('open')}
                          onhide={() => this.$('.Post-actions').removeClass('open')}
                        >
                          {controls}
                        </Dropdown>
                      </li>
                    ) : (
                      ''
                    )}
                  </ul>
                </aside>
                <footer className="Post-footer">
                  <ul>{listItems(this.footerItems().toArray())}</ul>
                </footer>
              </div>
            );
          })()}
      </article>
    );
  }

  config(isInitialized) {
    const $actions = this.$('.Post-actions');
    const $controls = this.$('.Post-controls');

    $actions.toggleClass('open', $controls.hasClass('open'));
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
   * @return {Array}
   */
  content() {
    return [];
  }

  /**
   * Get the post's classes.
   *
   * @param string classes
   * @returns {string[]}
   */
  classes(existing) {
    let classes = (existing || '').split(' ').concat(['Post']);

    const user = this.props.post.user();

    if (this.loading) {
      classes.push('Post--loading');
    }

    if (user && user === app.session.user) {
      classes.push('Post--by-actor');
    }

    if (user && app.current.discussion && app.current.discussion.attribute('startUserId') == user.id()) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  /**
   * Build an item list for the post's actions.
   *
   * @return {ItemList}
   */
  actionItems() {
    return new ItemList();
  }

  /**
   * Build an item list for the post's footer.
   *
   * @return {ItemList}
   */
  footerItems() {
    return new ItemList();
  }
}
