import Component from '../Component';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import Icon from './Icon';
import classList from '../utils/classList';

export interface IInfoTileAttrs extends ComponentAttrs {
  icon?: string;
  iconElement?: Mithril.Children;
}

export default class InfoTile<CustomAttrs extends IInfoTileAttrs = IInfoTileAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { icon, className, ...attrs } = vnode.attrs;

    return (
      <div className={classList('InfoTile', className)} {...attrs}>
        {this.icon()}
        <div className="InfoTile-text">{vnode.children}</div>
      </div>
    );
  }

  icon(): Mithril.Children {
    if (this.attrs.iconElement) return this.attrs.iconElement;

    if (!this.attrs.icon) return null;

    return <Icon name={classList(this.attrs.icon, 'InfoTile-icon')} />;
  }
}
