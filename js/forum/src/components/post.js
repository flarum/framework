import Component from 'flarum/Component';
import SubtreeRetainer from 'flarum/utils/SubtreeRetainer';
import Dropdown from 'flarum/components/Dropdown';
import PostControls from 'flarum/utils/PostControls';

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
    const controls = PostControls.controls(this.props.post, this).toArray();
    const attrs = this.attrs();

    attrs.className = 'post ' + (attrs.className || '');

    return (
      <article {...attrs}>
        {this.subtree.retain() || (
          <div>
            {controls.length ? Dropdown.component({
              children: controls,
              className: 'contextual-controls',
              buttonClass: 'btn btn-default btn-icon btn-controls btn-naked',
              menuClass: 'pull-right'
            }) : ''}

            {this.content()}
          </div>
        )}
      </article>
    );
  }

  /**
   * Get attributes for the post element.
   *
   * @return {Object}
   */
  attrs() {
  }

  /**
   * Get the post's content.
   *
   * @return {Object}
   */
  content() {
  }
}
