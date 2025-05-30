import Component from '../Component';
import type { ComponentAttrs } from '../Component';

import Icon from './Icon';

export interface IDetailedDropdownItemAttrs extends ComponentAttrs {
  /** The name of an icon to show in the dropdown item. */
  icon: string;
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
        <Icon name={this.attrs.active ? 'fas fa-check' : 'fas'} className="Button-icon" />
        <span className="DetailedDropdownItem-content">
          <Icon name={this.attrs.icon} className="Button-icon" />
          <span className="DetailedDropdownItem-label">
            <strong>{this.attrs.label}</strong>
            <span className="DetailedDropdownItem-description">{this.attrs.description}</span>
          </span>
        </span>
      </button>
    );
  }
}
