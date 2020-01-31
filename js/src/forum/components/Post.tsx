import Component, { ComponentProps } from '../../common/Component';
import Dropdown from '../../common/components/Dropdown';
import PostControls from '../utils/PostControls';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import PostModel from '../../common/models/Post';

export interface PostProps extends ComponentProps {
    post: PostModel;
}

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 *
 * @abstract
 */
export default class Post<T extends PostProps = PostProps> extends Component<PostProps> {
    loading = false;
    controlsOpen = false;

    subtree: SubtreeRetainer;

    oninit(vnode) {
        super.oninit(vnode);

        /**
         * Set up a subtree retainer so that the post will not be redrawn
         * unless new data comes in.
         */
        this.subtree = new SubtreeRetainer(
            () => this.props.post.freshness,
            () => {
                const user = this.props.post.user();
                return user?.freshness;
            },
            () => this.controlsOpen
        );
    }

    view() {
        const controls = PostControls.controls(this.props.post, this).toArray();
        const attrs = this.attrs();

        attrs.className = classNames(this.classes(attrs.className));

        return (
            <article {...attrs}>
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
            </article>
        );
    }

    onbeforeupdate(vnode) {
        super.onbeforeupdate(vnode);

        return this.subtree.update();
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        const $actions = this.$('.Post-actions');
        const $controls = this.$('.Post-controls');

        $actions.toggleClass('open', $controls.hasClass('open'));
    }

    /**
     * Get attributes for the post element.
     */
    attrs(): ComponentProps {
        return {};
    }

    /**
     * Get the post's content.
     */
    content() {
        return [];
    }

    classes(existing) {
        let classes = (existing || '').split(' ').concat(['Post']);

        if (this.loading) {
            classes.push('Post--loading');
        }

        if (this.props.post.user() === app.session.user) {
            classes.push('Post--by-actor');
        }

        return classes;
    }

    /**
     * Build an item list for the post's actions.
     */
    actionItems() {
        return new ItemList();
    }

    /**
     * Build an item list for the post's footer.
     */
    footerItems() {
        return new ItemList();
    }
}
