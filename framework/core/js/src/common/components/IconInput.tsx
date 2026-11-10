import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import LoadingIndicator from './LoadingIndicator';
import type IconPicker from './IconPicker';
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
 * The picker, loaded the first time one is opened. It reads every icon from
 * the page's stylesheets, which only matters to someone choosing an icon, so
 * it stays out of the main bundle.
 */
let Picker: typeof IconPicker | null = null;
let loading: Promise<typeof IconPicker> | null = null;

/** Whether the picker could not be fetched, so the field is typed in instead. */
let unavailable = false;

function loadPicker(): Promise<typeof IconPicker> {
  loading ??= import('./IconPicker').then((module) => (Picker = module.default));

  return loading;
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

    const toggle = (
      <Button
        className="Button Button--icon IconInput-toggle"
        icon={attrs.value?.trim() || 'fas fa-icons'}
        aria-label={label}
        title={label}
        aria-haspopup="dialog"
        aria-expanded={this.picking ? 'true' : 'false'}
        onclick={() => this.toggle()}
      />
    );

    // While the picker is open, its search takes the field's place, so only
    // one box to type in is ever shown: searching never changes the value.
    if (this.picking && Picker) {
      return (
        <div className="IconInput">
          <Picker
            value={attrs.value}
            toggle={toggle}
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
        </div>
      );
    }

    return (
      <div className="IconInput">
        <div className="IconInput-field">
          {toggle}
          <input
            className={classList('FormControl', className)}
            id={id}
            type="text"
            placeholder={placeholder}
            {...attrs}
            // The picker is where an icon is typed or chosen, so the field only
            // shows the value, and opens the picker when it's clicked or
            // reached with the keyboard (including through its label).
            readonly={!unavailable}
            aria-haspopup={unavailable ? undefined : 'dialog'}
            onfocus={() => this.open()}
            onclick={() => this.open()}
          />
        </div>

        {this.picking && <LoadingIndicator display="block" size="small" />}
      </div>
    );
  }

  toggle() {
    if (this.picking) {
      this.picking = false;
    } else {
      this.open();
    }
  }

  open() {
    if (this.picking || unavailable) return;

    this.picking = true;

    if (!Picker) {
      loadPicker().then(
        () => m.redraw(),
        () => {
          // The chunk could not be fetched (offline, or a deploy removed it):
          // the field takes typing again, so an icon can still be set.
          unavailable = true;
          this.picking = false;
          m.redraw();
        }
      );
    }
  }

  /**
   * Put focus back on the toggle once the picker — which held it — is gone,
   * so keyboard and screen reader users keep their place. The toggle moves
   * from the picker back to the field, so the field is drawn first.
   */
  refocus() {
    m.redraw.sync();
    this.$('.IconInput-toggle').trigger('focus');
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
