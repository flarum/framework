import app from '../../forum/app';
import Post from './Post';
import classList from '../../common/utils/classList';
import PostUser from './PostUser';
import PostMeta from './PostMeta';
import PostEdited from './PostEdited';
import EditPostComposer from './EditPostComposer';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import ComposerPostPreview from './ComposerPostPreview';

/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class CommentPost extends Post {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * If the post has been hidden, then this flag determines whether or not its
     * content has been expanded.
     *
     * @type {Boolean}
     */
    this.revealContent = false;

    /**
     * Whether or not the user hover card inside of PostUser is visible.
     * The property must be managed in CommentPost to be able to use it in the subtree check
     *
     * @type {Boolean}
     */
    this.cardVisible = false;

    this.subtree.check(
      () => this.cardVisible,
      () => this.isEditing(),
      () => this.revealContent
    );
  }

  content() {
    return super.content().concat([
      <header className="Post-header">
        <ul>{listItems(this.headerItems().toArray())}</ul>
      </header>,
      <div className="Post-body">
        {this.isEditing() ? <ComposerPostPreview className="Post-preview" composer={app.composer} /> : m.trust(this.attrs.post.contentHtml())}
      </div>,
    ]);
  }

  refreshContent() {
    const contentHtml = this.isEditing() ? '' : this.attrs.post.contentHtml();

    // If the post content has changed since the last render, we'll run through
    // all of the <script> tags in the content and evaluate them. This is
    // necessary because TextFormatter outputs them for e.g. syntax highlighting.
    if (this.contentHtml !== contentHtml) {
      this.$('.Post-body script').each(function () {
        const script = document.createElement('script');
        script.textContent = this.textContent;
        Array.from(this.attributes).forEach((attr) => script.setAttribute(attr.name, attr.value));
        this.parentNode.replaceChild(script, this);
      });
    }

    this.contentHtml = contentHtml;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.refreshContent();
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    this.refreshContent();
  }

  isEditing() {
    return app.composer.bodyMatches(EditPostComposer, { post: this.attrs.post });
  }

  elementAttrs() {
    const post = this.attrs.post;
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className, 'CommentPost', {
      'Post--renderFailed': post.renderFailed(),
      'Post--hidden': post.isHidden(),
      'Post--edited': post.isEdited(),
      revealContent: this.revealContent,
      editing: this.isEditing(),
    });

    if (this.isEditing()) attrs['aria-busy'] = 'true';

    return attrs;
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
   * @return {ItemList<import('mithril').Children>}
   */
  headerItems() {
    const items = new ItemList();
    const post = this.attrs.post;

    items.add(
      'user',
      <PostUser
        post={post}
        cardVisible={this.cardVisible}
        oncardshow={() => {
          this.cardVisible = true;
          m.redraw();
        }}
        oncardhide={() => {
          this.cardVisible = false;
          m.redraw();
        }}
      />,
      100
    );
    items.add('meta', <PostMeta post={post} />);

    if (post.isEdited() && !post.isHidden()) {
      items.add('edited', <PostEdited post={post} />);
    }

    // If the post is hidden, add a button that allows toggling the visibility
    // of the post's content.
    if (post.isHidden()) {
      items.add(
        'toggle',
        <Button className="Button Button--default Button--more" icon="fas fa-ellipsis-h" onclick={this.toggleContent.bind(this)} />
      );
    }

    return items;
  }
}
