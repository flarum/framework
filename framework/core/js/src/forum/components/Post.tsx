import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import Dropdown from '../../common/components/Dropdown';
import PostControls from '../utils/PostControls';
import listItems, { ModdedChildrenWithItemName } from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import type PostModel from '../../common/models/Post';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import type Mithril from 'mithril';

export interface IPostAttrs extends ComponentAttrs {
  post: PostModel;
}

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Post<CustomAttrs extends IPostAttrs = IPostAttrs> extends Component<CustomAttrs> {
  /**
   * May be set by subclasses.
   */
  loading = false;

  /**
   * Ensures that the post will not be redrawn
   * unless new data comes in.
   */
  subtree!: SubtreeRetainer;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.loading = false;

    this.subtree = new SubtreeRetainer(
      () => this.loading,
      () => this.attrs.post.freshness,
      () => {
        const user = this.attrs.post.user();
        return user && user.freshness;
      }
    );
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const attrs = this.elementAttrs();

    attrs.className = this.classes(attrs.className as string | undefined).join(' ');

    const controls = PostControls.controls(this.attrs.post, this).toArray();
    const footerItems = this.footerItems().toArray();

    return (
      <article {...attrs}>
        <div>{this.viewItems(controls, footerItems).toArray()}</div>
      </article>
    );
  }

  viewItems(controls: Mithril.Children[], footerItems: ModdedChildrenWithItemName[]): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('content', this.loading ? <LoadingIndicator /> : this.content(), 100);

    items.add(
      'actions',
      <aside className="Post-actions">
        <ul>
          {listItems(this.actionItems().toArray())}
          {!!controls.length && (
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
          )}
        </ul>
      </aside>,
      90
    );

    items.add('footer', <footer className="Post-footer">{footerItems.length > 0 ? <ul>{listItems(footerItems)}</ul> : null}</footer>, 80);

    return items;
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeupdate(vnode);

    return this.subtree.needsRebuild();
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    const $actions = this.$('.Post-actions');
    const $controls = this.$('.Post-controls');

    $actions.toggleClass('openWithin', $controls.hasClass('open'));
  }

  /**
   * Get attributes for the post element.
   */
  elementAttrs(): Record<string, unknown> {
    return {};
  }

  /**
   * Get the post's content.
   */
  content(): Mithril.Children {
    // TODO: [Flarum 2.0] return `null`
    return [];
  }

  /**
   * Get the post's classes.
   */
  classes(existing?: string): string[] {
    let classes = (existing || '').split(' ').concat(['Post']);

    const user = this.attrs.post.user();
    const discussion = this.attrs.post.discussion();

    if (this.loading) {
      classes.push('Post--loading');
    }

    if (user && user === app.session.user) {
      classes.push('Post--by-actor');
    }

    if (user && user === discussion.user()) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  /**
   * Build an item list for the post's actions.
   */
  actionItems(): ItemList<Mithril.Children> {
    return new ItemList();
  }

  /**
   * Build an item list for the post's footer.
   */
  footerItems(): ItemList<ModdedChildrenWithItemName> {
    return new ItemList<ModdedChildrenWithItemName>();
  }
}
