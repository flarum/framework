import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

export default class SubscriptionMenuItem extends Component {
  view() {
    return (
      <button className="SubscriptionMenuItem hasIcon" onclick={this.attrs.onclick}>
        {this.attrs.active ? icon('fas fa-check', {className: 'Button-icon'}) : ''}
        <span className="SubscriptionMenuItem-label">
          {icon(this.attrs.icon, {className: 'Button-icon'})}
          <strong>{this.attrs.label}</strong>
          <span className="SubscriptionMenuItem-description">{this.attrs.description}</span>
        </span>
      </button>
    );
  }
}
