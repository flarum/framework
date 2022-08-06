import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';

export interface IDataSegmentAttrs extends ComponentAttrs {
  label: Mithril.Children;
  value: Mithril.Children;
}

export default class DataSegment<CustomAttrs extends IDataSegmentAttrs = IDataSegmentAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <div className="DataSegment">
        <div className="DataSegment-label">{this.attrs.label}:</div>
        <div className="DataSegment-value">{this.attrs.value}</div>
      </div>
    );
  }
}
