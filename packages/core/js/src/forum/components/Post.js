import app from '../../forum/app';
import Component from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import Dropdown from '../../common/components/Dropdown';
import PostControls from '../utils/PostControls';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import LoadingIndicator from '../../common/components/LoadingIndicator';

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 *
 * ### Attrs
 *
 * - `post`
 *
 * @abstract
 */
export default class Post extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * May be set by subclasses.
     */
    this.loading = false;

    /**
     * Set up a subtree retainer so that the post will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    this.subtree = new SubtreeRetainer(
      () => this.loading,
      () => this.attrs.post.freshness,
      () => {
        const user = this.attrs.post.user();
        return user && user.freshness;
      },
      () => this.controlsOpen
    );
  }

  view() {
    const attrs = this.elementAttrs();

    attrs.className = this.classes(attrs.className).join(' ');

    const controls = PostControls.controls(this.attrs.post, this).toArray();
    const footerItems = this.footerItems().toArray();

    return (
      <article {...attrs}>
        <div>
          {this.loading ? <LoadingIndicator /> : this.content()}
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
                    onshow={() => this.$('.Post-controls').addClass('open')}
                    onhide={() => this.$('.Post-controls').removeClass('open')}
                    accessibleToggleLabel={app.translator.trans('core.forum.post_controls.toggle_dropdown_accessible_label')}
                  >
                    {controls}
                  </Dropdown>
                </li>
              ) : (
                ''
              )}
            </ul>
          </aside>
          <footer className="Post-footer">{footerItems.length ? <ul>{listItems(footerItems)}</ul> : null}</footer>
        </div>
      </article>
    );
  }

  onbeforeupdate(vnode) {
    super.onbeforeupdate(vnode);

    return this.subtree.needsRebuild();
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    const $actions = this.$('.Post-actions');
    const $controls = this.$('.Post-controls');

    $actions.toggleClass('open', $controls.hasClass('open'));
  }

  /**
   * Get attributes for the post element.
   *
   * @return {Record<string, unknown>}
   */
  elementAttrs() {
    return {};
  }

  /**
   * Get the post's content.
   *
   * @return {import('mithril').Children}
   */
  content() {
    // TODO: [Flarum 2.0] return `null`
    return [];
  }

  /**
   * Get the post's classes.
   *
   * @param {string} existing
   * @returns {string[]}
   */
  classes(existing) {
    let classes = (existing || '').split(' ').concat(['Post']);

    const user = this.attrs.post.user();
    const discussion = this.attrs.post.discussion();

    if (this.loading) {
      classes.push('Post--loading');
    }

    if (user && user === app.session.user) {
      classes.push('Post--by-actor');
    }

    if (user && user?.id() === discussion.attribute('startUserId')) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  /**
   * Build an item list for the post's actions.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  actionItems() {
    return new ItemList();
  }

  /**
   * Build an item list for the post's footer.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  footerItems() {
    return new ItemList();
  }
}
