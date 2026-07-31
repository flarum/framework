import type Mithril from 'mithril';

import Component from '../Component';
import type { ComponentAttrs } from '../Component';

import Icon from './Icon';

export interface IDetailedDropdownItemAttrs extends ComponentAttrs {
  /**
   * The name of an icon to show in the dropdown item, or a rendered icon —
   * which lets the caller pass attributes of its own, e.g. `noStyleOverride`
   * for an icon whose style carries meaning.
   */
  icon: string | Mithril.Children;
  /** The label of the dropdown item. */
  label: string;
  /** The description of the item. */
  description: string;
  /** An action to take when the item is clicked. */
  onclick: () => void;
  /** Whether the item is the current active/selected option. */
  active?: boolean;
}

export default class DetailedDropdownItem<
  CustomAttrs extends IDetailedDropdownItemAttrs = IDetailedDropdownItemAttrs
> extends Component<CustomAttrs> {
  view() {
    return (
      <button type="button" className="DetailedDropdownItem hasIcon" onclick={this.attrs.onclick}>
        <span className="DetailedDropdownItem-checkIcon">{this.attrs.active && <Icon name="fas fa-check" className="Button-icon" />}</span>
        <span className="DetailedDropdownItem-content">
          {typeof this.attrs.icon === 'string' ? <Icon name={this.attrs.icon} className="Button-icon" /> : this.attrs.icon}
          <span className="DetailedDropdownItem-label">
            <strong>{this.attrs.label}</strong>
            <span className="DetailedDropdownItem-description">{this.attrs.description}</span>
          </span>
        </span>
      </button>
    );
  }
}
