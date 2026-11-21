import app from '../app';
import Dropdown from '../../common/components/Dropdown';
import type { IDropdownAttrs } from '../../common/components/Dropdown';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';

import Icon from '../../common/components/Icon';
import type { TooltipAttrs } from '../../common/components/Tooltip';

export interface IHeaderDropdownAttrs extends IDropdownAttrs {
  state: any;
}

export default abstract class HeaderDropdown<CustomAttrs extends IHeaderDropdownAttrs = IHeaderDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: IHeaderDropdownAttrs) {
    attrs.className = classList('HeaderDropdown', attrs.className);
    attrs.buttonClassName ||= 'Button Button--flat';
    attrs.menuClassName ||= 'Dropdown-menu--right';
    attrs.label ||= extractText(app.translator.trans('core.forum.notifications.tooltip'));

    super.initAttrs(attrs);
  }

  protected getButtonTooltip(button: Mithril.Vnode<any, any>): TooltipAttrs {
    return {
      text: this.attrs.tooltip || extractText(this.attrs.label),
      ariaLabel: button.attrs['aria-label'],
      container: 'body',
    };
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    const newCount = this.getNewCount();

    const vdom = super.getButton(children);

    vdom.attrs.className = classList(vdom.attrs.className, [newCount && 'new']);
    vdom.attrs.onclick = this.onclick.bind(this);

    return vdom;
  }

  getButtonContent(): Mithril.ChildArray {
    const unread = this.getUnreadCount();

    return [
      this.attrs.icon ? <Icon name={this.attrs.icon} className="Button-icon" /> : null,
      unread > 0 && (
        <span className="Bubble HeaderDropdownBubble" data-digits={String(unread).length}>
          {unread}
        </span>
      ),
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
      // A list that fails to load has shown its own error.
      Promise.resolve(this.attrs.state?.load()).catch(() => {});
    }
  }

  abstract getNewCount(): number;
  abstract getUnreadCount(): number;
  abstract getContent(): Mithril.Children;
  abstract goToRoute(): void;
}
