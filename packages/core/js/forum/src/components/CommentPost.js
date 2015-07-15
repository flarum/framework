import Post from 'flarum/components/Post';
import classList from 'flarum/utils/classList';
import PostUser from 'flarum/components/PostUser';
import PostMeta from 'flarum/components/PostMeta';
import PostEdited from 'flarum/components/PostEdited';
import EditPostComposer from 'flarum/components/EditPostComposer';
import Composer from 'flarum/components/Composer';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';
import icon from 'flarum/helpers/icon';

/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 *
 * ### Props
 *
 * - `post`
 */
export default class CommentPost extends Post {
  constructor(...args) {
    super(...args);

    /**
     * If the post has been hidden, then this flag determines whether or not its
     * content has been expanded.
     *
     * @type {Boolean}
     */
    this.revealContent = false;

    // Create an instance of the component that displays the post's author so
    // that we can force the post to rerender when the user card is shown.
    this.postUser = new PostUser({post: this.props.post});
    this.subtree.check(() => this.postUser.cardVisible);
  }

  content() {
    return [
      <header className="post-header"><ul>{listItems(this.headerItems().toArray())}</ul></header>,
      <div className="post-body">{m.trust(this.props.post.contentHtml())}</div>,
      <aside className="post-footer"><ul>{listItems(this.footerItems().toArray())}</ul></aside>,
      <aside className="post-actions"><ul>{listItems(this.actionItems().toArray())}</ul></aside>
    ];
  }

  attrs() {
    const post = this.props.post;

    return {
      className: classList({
        'comment-post': true,
        'is-hidden': post.isHidden(),
        'is-edited': post.isEdited(),
        'reveal-content': this.revealContent,
        'editing': app.composer.component instanceof EditPostComposer &&
          app.composer.component.props.post === post &&
          app.composer.position !== Composer.PositionEnum.MINIMIZED
      })
    };
  }

  /**
   * Toggle the visibility of a hidden post's content.
   */
  toggleContent() {
    this.revealContent = !this.revealContent;
  }

  /**
   * Build an item list for the post's header.
   *
   * @return {ItemList}
   */
  headerItems() {
    const items = new ItemList();
    const post = this.props.post;
    const props = {post};

    items.add('user', this.postUser.render(), 100);
    items.add('meta', PostMeta.component(props));

    if (post.isEdited() && !post.isHidden()) {
      items.add('edited', PostEdited.component(props));
    }

    // If the post is hidden, add a button that allows toggling the visibility
    // of the post's content.
    if (post.isHidden()) {
      items.add('toggle', (
        <button
          className="btn btn-default btn-more"
          onclick={this.toggleContent.bind(this)}>
          {icon('ellipsis-h')}
        </button>
      ));
    }

    return items;
  }

  /**
   * Build an item list for the post's footer.
   *
   * @return {ItemList}
   */
  footerItems() {
    return new ItemList();
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
