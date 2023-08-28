import Component from '../Component';
import icon from '../helpers/icon';

export default class DetailedDropdownItem extends Component {
  view() {
    return (
      <button className="DetailedDropdownItem hasIcon" onclick={this.attrs.onclick}>
        {icon(this.attrs.active ? 'fas fa-check' : 'fas', { className: 'Button-icon' })}
        <span className="DetailedDropdownItem-content">
          {icon(this.attrs.icon, { className: 'Button-icon' })}
          <span className="DetailedDropdownItem-label">
            <strong>{this.attrs.label}</strong>
            <span className="DetailedDropdownItem-description">{this.attrs.description}</span>
          </span>
        </span>
      </button>
    );
  }
}
