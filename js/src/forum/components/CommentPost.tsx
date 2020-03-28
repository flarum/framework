import Post from './Post';
import PostUser from './PostUser';
import PostMeta from './PostMeta';
import PostEdited from './PostEdited';
// import EditPostComposer from './EditPostComposer';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import { Vnode } from 'mithril';

/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 */
export default class CommentPost extends Post {
    /**
     * If the post has been hidden, then this flag determines whether or not its
     * content has been expanded.
     */
    revealContent: boolean = false;

    postUser!: Vnode<{}, PostUser>;

    oninit(vnode) {
        super.oninit(vnode);

        // Create an instance of the component that displays the post's author so
        // that we can force the post to rerender when the user card is shown.
        this.postUser = <PostUser post={this.props.post} />;

        this.subtree.check(
            () => this.postUser.state?.cardVisible,
            () => this.revealContent,
            () => this.isEditing()
        );
    }

    content() {
        return super.content().concat([
            <header className="Post-header">
                <ul>{listItems(this.headerItems().toArray())}</ul>
            </header>,
            <div className="Post-body">
                {this.isEditing() ? <div className="Post-preview" config={this.configPreview.bind(this)} /> : m.trust(this.props.post.contentHtml())}
            </div>,
        ]);
    }

    onupdate(vnode) {
        super.onupdate(vnode);

        const contentHtml = this.isEditing() ? '' : this.props.post.contentHtml();

        // TODO: idk what this is supposed to be

        // If the post content has changed since the last render, we'll run through
        // all of the <script> tags in the content and evaluate them. This is
        // necessary because TextFormatter outputs them for e.g. syntax highlighting.
        if (vnode.contentHtml !== contentHtml) {
            this.$('.Post-body script').each(function () {
                eval.call(window, $(this).text());
            });
        }

        vnode.contentHtml = contentHtml;
    }

    isEditing() {
        return false; // TODO
        // return app.composer?.component instanceof EditPostComposer &&
        //     app.composer.component.props.post === this.props.post;
    }

    attrs() {
        const post = this.props.post;
        const attrs = super.attrs();

        attrs.className = classNames(
            attrs.className,
            'CommentPost',
            post.isHidden() && 'Post--hidden',
            post.isEdited() && 'Post--edited',
            this.revealContent && 'revealContent',
            this.isEditing() && 'editing'
        );

        return attrs;
    }

    // TODO change so it works
    configPreview(element, isInitialized, context) {
        if (isInitialized) return;

        // Every 50ms, if the composer content has changed, then update the post's
        // body with a preview.
        let preview;
        const updatePreview = () => {
            const content = app.composer.component.content();

            if (preview === content) return;

            preview = content;

            s9e.TextFormatter.preview(preview || '', element);
        };
        updatePreview();

        const updateInterval = setInterval(updatePreview, 50);
        context.onunload = () => clearInterval(updateInterval);
    }

    /**
     * Toggle the visibility of a hidden post's content.
     */
    toggleContent() {
        this.revealContent = !this.revealContent;
    }

    /**
     * Build an item list for the post's header.
     */
    headerItems(): ItemList {
        const items = new ItemList();
        const post = this.props.post;
        const props = { post };

        items.add('user', <PostUser post={this.props.post} />, 100);
        items.add('meta', <PostMeta {...props} />);

        if (post.isEdited() && !post.isHidden()) {
            items.add('edited', <PostEdited {...props} />);
        }

        // If the post is hidden, add a button that allows toggling the visibility
        // of the post's content.
        if (post.isHidden()) {
            items.add(
                'toggle',
                Button.component({
                    className: 'Button Button--default Button--more',
                    icon: 'fas fa-ellipsis-h',
                    onclick: this.toggleContent.bind(this),
                })
            );
        }

        return items;
    }
}
