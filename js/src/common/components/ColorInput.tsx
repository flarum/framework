import type Mithril from 'mithril';

import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import icon from '../helpers/icon';

export default class ColorInput extends Component {
  value?: string;

  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { className, ...attrs } = this.attrs;
    const value = attrs.bidi?.() || attrs.value;

    return (
      <div className="Color-input">
        <input className={classList('FormControl', className)} {...attrs} />

        <span className="Color-input--icon">{icon('fas fa-exclamation-circle')}</span>

        <div className="Color-input--preview" style={{ '--input-value': value }} />
      </div>
    );
  }
}
