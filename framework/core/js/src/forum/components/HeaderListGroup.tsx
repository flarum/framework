import type { ComponentAttrs } from '../../common/Component';
import Component from '../../common/Component';
import type Mithril from 'mithril';
import listItems from '../../common/helpers/listItems';

export interface IHeaderListGroupAttrs extends ComponentAttrs {
  label: Mithril.Children;
}

export default class HeaderListGroup<CustomAttrs extends IHeaderListGroupAttrs = IHeaderListGroupAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return (
      <div className="HeaderListGroup">
        <div className="HeaderListGroup-header">{vnode.attrs.label}</div>
        <ul className="HeaderListGroup-content">{listItems(vnode.children as any[])}</ul>
      </div>
    );
  }
}
