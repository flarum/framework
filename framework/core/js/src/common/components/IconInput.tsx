import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import IconPicker from './IconPicker';
import classList from '../utils/classList';
import type Mithril from 'mithril';

export interface IIconInputAttrs extends ComponentAttrs {
  value: string;
  id?: string;
  placeholder?: string;
  onchange?: (event: { target: { value: string } }) => void;
  oninput?: (event: { target: { value: string } }) => void;
}

/**
 * A text field for a Font Awesome icon class, with a preview of the icon that
 * opens an `IconPicker`. Typing any class still works exactly as before — the
 * picker only fills the field — so Pro, Kit and custom icons stay available.
 *
 * Like `ColorPreviewInput`, it accepts `bidi`, so it can stand in for a plain
 * `<input>`.
 */
export default class IconInput<CustomAttrs extends IIconInputAttrs = IIconInputAttrs> extends Component<CustomAttrs> {
  picking = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { className, id, placeholder, ...attrs } = this.attrs;
    const label = app.translator.trans('core.lib.icon_picker.open_button', {}, true);

    return (
      <div className="IconInput">
        <div className="IconInput-field">
          <Button
            className="Button Button--icon IconInput-toggle"
            icon={attrs.value?.trim() || 'fas fa-icons'}
            aria-label={label}
            title={label}
            aria-haspopup="dialog"
            aria-expanded={this.picking ? 'true' : 'false'}
            onclick={() => (this.picking = !this.picking)}
          />
          <input className={classList('FormControl', className)} id={id} type="text" placeholder={placeholder} {...attrs} />
        </div>

        {this.picking && (
          <IconPicker
            value={attrs.value}
            onpick={(icon: string) => {
              this.picking = false;
              this.change(icon);
              this.refocus();
            }}
            onclose={(reason: 'escape' | 'outside') => {
              this.picking = false;
              m.redraw();
              if (reason === 'escape') this.refocus();
            }}
          />
        )}
      </div>
    );
  }

  /**
   * Put focus back on the toggle once the picker — which held it — is gone,
   * so keyboard and screen reader users keep their place.
   */
  refocus() {
    requestAnimationFrame(() => this.$('.IconInput-toggle').trigger('focus'));
  }

  /**
   * Report a picked icon the way the field itself would. A `bidi` handler
   * reads `this.value`; any other reads `event.target.value`. This satisfies
   * both.
   */
  change(value: string) {
    const target = { value };

    this.attrs.value = value;
    this.attrs.oninput?.call(target, { target });
    this.attrs.onchange?.call(target, { target });
  }
}
