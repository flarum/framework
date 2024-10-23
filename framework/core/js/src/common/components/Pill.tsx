import Component, { type ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import Button from './Button';
import classList from '../utils/classList';

export interface IPillAttrs extends ComponentAttrs {
  deletable?: boolean;
  ondelete?: () => void;
}

export default class Pill<CustomAttrs extends IPillAttrs = IPillAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return (
      <span className={classList('Pill', this.attrs.className)}>
        <span className="Pill-content">{vnode.children}</span>
        {this.attrs.deletable && <Button type="button" className="Button Button--icon" icon="fas fa-times" onclick={this.attrs.ondelete} />}
      </span>
    );
  }
}
