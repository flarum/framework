import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import app from '../app';

export interface ILabelValueAttrs extends ComponentAttrs {
  label: Mithril.Children;
  value: Mithril.Children;
}

/**
 * A generic component for displaying a label and value inline.
 * Created to avoid reinventing the wheel.
 *
 * `label: value`
 */
export default class LabelValue<CustomAttrs extends ILabelValueAttrs = ILabelValueAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    return (
      <div className="LabelValue">
        <div className="LabelValue-label">
          {app.translator.trans('core.lib.data_segment.label', {
            label: this.attrs.label,
          })}
        </div>
        <div className="LabelValue-value">{this.attrs.value}</div>
      </div>
    );
  }
}
