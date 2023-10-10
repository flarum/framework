import Component from '../../common/Component';
import classList from '../../common/utils/classList';

import Icon from '../../common/components/Icon';

export default class AdminHeader extends Component {
  view(vnode) {
    return [
      <div className={classList(['AdminHeader', this.attrs.className])}>
        <div className="container">
          <h2>
            <Icon name={this.attrs.icon} />
            {vnode.children}
          </h2>
          <div className="AdminHeader-description">{this.attrs.description}</div>
        </div>
      </div>,
    ];
  }
}
