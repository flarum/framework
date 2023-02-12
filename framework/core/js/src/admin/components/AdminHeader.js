import Component from '../../common/Component';
import classList from '../../common/utils/classList';
import icon from '../../common/helpers/icon';

export default class AdminHeader extends Component {
  view(vnode) {
    return [
      <div className={classList(['AdminHeader', this.attrs.className])}>
        <div className="container">
          <h1>
            {icon(this.attrs.icon)}
            {vnode.children}
          </h1>
          <div className="AdminHeader-description">{this.attrs.description}</div>
        </div>
      </div>,
    ];
  }
}
