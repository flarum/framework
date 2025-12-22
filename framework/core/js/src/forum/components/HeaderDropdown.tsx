import app from '../app';
import Dropdown from '../../common/components/Dropdown';
import type { IDropdownAttrs } from '../../common/components/Dropdown';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';

import Icon from '../../common/components/Icon';

export interface IHeaderDropdownAttrs extends IDropdownAttrs {
  state: any;
}

export default abstract class HeaderDropdown<CustomAttrs extends IHeaderDropdownAttrs = IHeaderDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: IHeaderDropdownAttrs) {
    attrs.className = classList('HeaderDropdown', attrs.className);
    attrs.buttonClassName ||= 'Button Button--flat';
    attrs.menuClassName ||= 'Dropdown-menu--right';
    attrs.label ||= extractText(app.translator.trans('core.forum.notifications.tooltip'));
    // attrs.icon ||= 'fas fa-bell';
    //
    // // For best a11y support, both `title` and `aria-label` should be used
    // attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.forum.notifications.toggle_dropdown_accessible_label'));

    super.initAttrs(attrs);
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    const newCount = this.getNewCount();

    const vdom = super.getButton(children);

    vdom.attrs.title = this.attrs.label;

    vdom.attrs.className = classList(vdom.attrs.className, [newCount && 'new']);
    vdom.attrs.onclick = this.onclick.bind(this);

    return vdom;
  }

  getButtonContent(): Mithril.ChildArray {
    const unread = this.getUnreadCount();

    return [
      this.attrs.icon ? <Icon name={this.attrs.icon} className="Button-icon" /> : null,
      unread !== 0 && <span className="Bubble HeaderDropdownBubble">{unread}</span>,
      <span className="Button-label">
        <span className="Button-labelText">{this.attrs.label}</span>
      </span>,
    ];
  }

  getMenu() {
    return (
      <div className={classList('Dropdown-menu', this.attrs.menuClassName)} onclick={this.menuClick.bind(this)}>
        {this.showing && this.getContent()}
      </div>
    );
  }

  menuClick(e: MouseEvent) {
    // Don't close the notifications dropdown if the user is opening a link in a
    // new tab or window.
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.button === 1) e.stopPropagation();
  }

  onclick() {
    if (app.drawer.isOpen()) {
      this.goToRoute();
    } else {
      this.attrs.state?.load();
    }
  }

  abstract getNewCount(): number;
  abstract getUnreadCount(): number;
  abstract getContent(): Mithril.Children;
  abstract goToRoute(): void;
}
