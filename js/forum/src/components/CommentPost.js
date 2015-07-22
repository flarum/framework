import Post from 'flarum/components/Post';
import classList from 'flarum/utils/classList';
import PostUser from 'flarum/components/PostUser';
import PostMeta from 'flarum/components/PostMeta';
import PostEdited from 'flarum/components/PostEdited';
import EditPostComposer from 'flarum/components/EditPostComposer';
import Composer from 'flarum/components/Composer';
import ItemList from 'flarum/utils/ItemList';
import formatText from 'flarum/utils/formatText';
import listItems from 'flarum/helpers/listItems';
import Button from 'flarum/components/Button';

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
    this.subtree.check(
      () => this.postUser.cardVisible,
      () => this.props.post.editedContent,
      () => this.isEditing()
    );
  }

  content() {
    const content = this.isEditing()
      ? formatText(this.props.post.editedContent)
      : this.props.post.contentHtml();

    return [
      <header className="Post-header"><ul>{listItems(this.headerItems().toArray())}</ul></header>,
      <div className="Post-body">{m.trust(content)}</div>,
      <footer className="Post-footer"><ul>{listItems(this.footerItems().toArray())}</ul></footer>,
      <aside className="Post-actions"><ul>{listItems(this.actionItems().toArray())}</ul></aside>
    ];
  }

  isEditing() {
    return app.composer.component instanceof EditPostComposer &&
      app.composer.component.props.post === this.props.post &&
      app.composer.position !== Composer.PositionEnum.MINIMIZED;
  }

  attrs() {
    const post = this.props.post;

    return {
      className: classList({
        'CommentPost': true,
        'hidden': post.isHidden(),
        'edited': post.isEdited(),
        'revealContent': this.revealContent,
        'editing': this.isEditing()
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
        Button.component({
          className: 'Button Button--default Button--more',
          icon: 'ellipsis-h',
          onclick: this.toggleContent.bind(this)
        })
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
