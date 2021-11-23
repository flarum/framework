import type Mithril from 'mithril';

import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import icon from '../helpers/icon';

export default class ColorPreviewInput extends Component {
  value?: string;

  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { className, ...attrs } = this.attrs;
    const value = attrs.bidi?.() || attrs.value;

    attrs.type ||= 'text';

    return (
      <div className="ColorInput">
        <input className={classList('FormControl', className)} {...attrs} />

        <span className="ColorInput-icon" role="presentation">
          {icon('fas fa-exclamation-circle')}
        </span>

        <div className="ColorInput-preview" style={{ '--input-value': value }} role="presentation" />
      </div>
    );
  }
}
