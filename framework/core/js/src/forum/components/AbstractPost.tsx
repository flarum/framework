import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import Dropdown from '../../common/components/Dropdown';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import type Mithril from 'mithril';
import type User from '../../common/models/User';

export interface IAbstractPostAttrs extends ComponentAttrs {}

/**
 * This component can be used on any type of model with an author and content.
 * Subclasses are specialized for specific types of models.
 */
export default abstract class AbstractPost<CustomAttrs extends IAbstractPostAttrs = IAbstractPostAttrs> extends Component<CustomAttrs> {
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
      () => this.freshness(),
      () => {
        const user = this.user();
        return user && user.freshness;
      }
    );
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const attrs = this.elementAttrs();

    attrs.className = this.classes(attrs.className as string | undefined).join(' ');

    const controls = this.controls();
    const footerItems = this.footerItems().toArray();

    return (
      <article {...attrs}>
        {this.header()}
        <div className="Post-container">
          <div className="Post-side">{this.sideItems().toArray()}</div>
          <div className="Post-main">
            {this.loading ? <LoadingIndicator /> : this.content()}
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
            </aside>
            <footer className="Post-footer">{footerItems.length ? <ul>{listItems(footerItems)}</ul> : null}</footer>
          </div>
        </div>
      </article>
    );
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

  elementAttrs(): Record<string, unknown> {
    return {};
  }

  header(): Mithril.Children {
    return null;
  }

  content(): Mithril.Children[] {
    return [];
  }

  classes(existing?: string): string[] {
    let classes = (existing || '').split(' ').concat(['Post']);

    const user = this.user();

    if (this.loading) {
      classes.push('Post--loading');
    }

    if (user && user === app.session.user) {
      classes.push('Post--by-actor');
    }

    if (this.createdByStarter()) {
      classes.push('Post--by-start-user');
    }

    return classes;
  }

  actionItems(): ItemList<Mithril.Children> {
    return new ItemList();
  }

  footerItems(): ItemList<Mithril.Children> {
    return new ItemList();
  }

  sideItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('avatar', this.avatar(), 100);

    return items;
  }

  abstract user(): User | null | false;
  abstract controls(): Mithril.Children[];
  abstract freshness(): Date;
  abstract createdByStarter(): boolean;

  avatar(): Mithril.Children {
    return null;
  }
}
