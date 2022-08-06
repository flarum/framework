import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import classList from 'flarum/common/utils/classList';

interface LabelAttrs extends ComponentAttrs {
  type: 'success' | 'error' | 'neutral' | 'warning';
}

export default class Label extends Component<LabelAttrs> {
  view(vnode: Mithril.Vnode<LabelAttrs, this>) {
    const { className, type, ...attrs } = this.attrs;

    return (
      <span className={classList(['Label', `Label--${this.attrs.type}`, className])} {...attrs}>
        {vnode.children}
      </span>
    );
  }
}
