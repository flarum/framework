import type Mithril from 'mithril';

import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';

import Icon from './Icon';

export default class ColorPreviewInput extends Component {
  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { className, id, ...attrs } = this.attrs;

    attrs.type ||= 'text';

    attrs.onblur = () => {
      if (attrs.value.length === 4) {
        attrs.value = attrs.value.replace(/#([a-f0-9])([a-f0-9])([a-f0-9])/, '#$1$1$2$2$3$3');
        attrs.onchange?.({ target: { value: attrs.value } });
      }

      // Validate the color
      if (!/^#[a-f0-9]{6}$/i.test(attrs.value)) {
        attrs.value = '#000000';
        attrs.onchange?.({ target: { value: attrs.value } });
      }
    };

    return (
      <div className="ColorInput">
        <input className={classList('FormControl', className)} id={id} {...attrs} />

        <span className="ColorInput-icon" role="presentation">
          <Icon name={'fas fa-exclamation-circle'} />
        </span>

        <input className="ColorInput-preview" {...attrs} type="color" />
      </div>
    );
  }
}
