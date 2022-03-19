import Component, { ComponentAttrs } from '../../common/Component';
import classList from '../../common/utils/classList';
import icon from '../../common/helpers/icon';
import type Mithril from 'mithril';

export interface IAdminHeaderAttrs extends ComponentAttrs {
  icon: string;
  description: string;
}

export default class AdminHeader<CustomAttrs extends IAdminHeaderAttrs = IAdminHeaderAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return [
      <div className={classList(['AdminHeader', this.attrs.className])}>
        <div className="container">
          <h2>
            {icon(this.attrs.icon)}
            {vnode.children}
          </h2>
          <div className="AdminHeader-description">{this.attrs.description}</div>
        </div>
      </div>,
    ];
  }
}
