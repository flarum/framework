import type Mithril from 'mithril';

import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import icon from '../helpers/icon';

export default class ColorPreviewInput extends Component {
  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { className, id, ...attrs } = this.attrs;

    attrs.type ||= 'text';

    return (
      <div className="ColorInput">
        <input className={classList('FormControl', className)} id={id} {...attrs} />

        <span className="ColorInput-icon" role="presentation">
          {icon('fas fa-exclamation-circle')}
        </span>

        <input className="ColorInput-preview" {...attrs} type="color" />
      </div>
    );
  }
}
