import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import Icon from './Icon';
import LoadingIndicator from './LoadingIndicator';
import classList from '../utils/classList';
import fontAwesomeIcons, { regularFontAwesomeIcons, type FontAwesomeIcon } from '../utils/fontAwesomeIcons';
import type Mithril from 'mithril';

export interface IIconPickerAttrs extends ComponentAttrs {
  /** The class currently chosen, e.g. `fas fa-bolt`. */
  value?: string;
  /** Called with the full class of the icon picked. */
  onpick: (icon: string) => void;
  /** Called when the picker should close: Escape, or a click outside it. */
  onclose: () => void;
}

type IconStyle = 'solid' | 'regular' | 'brands';

const PREFIX: Record<IconStyle, string> = { solid: 'fas', regular: 'far', brands: 'fab' };

/**
 * How many icons are drawn at once. Enough to browse; searching narrows the
 * rest.
 */
const SHOWN = 240;

/**
 * A searchable grid of the Font Awesome icons the forum can draw, in solid,
 * regular and brands styles. See `IconInput`, which pairs it with a text field
 * that still accepts any class.
 */
export default class IconPicker<CustomAttrs extends IIconPickerAttrs = IIconPickerAttrs> extends Component<CustomAttrs> {
  query = '';
  style: IconStyle = 'solid';
  regular: FontAwesomeIcon[] | null = null;
  closeWatcher?: CloseWatcher;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const value = this.attrs.value || '';

    if (/\b(fab|fa-brands)\b/.test(value)) this.style = 'brands';
    else if (/\b(far|fa-regular)\b/.test(value)) this.setStyle('regular');
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // Escape closes the picker, not a modal around it. Like Dropdown, take the
    // newest CloseWatcher where there is one, which the browser asks before
    // the modal's. Elsewhere the modal listens for Escape on keyup, so catch
    // that on its way down.
    if ('CloseWatcher' in window) {
      this.closeWatcher = new CloseWatcher();
      this.closeWatcher.onclose = () => {
        this.closeWatcher = undefined;
        this.attrs.onclose();
      };
    } else {
      document.addEventListener('keyup', this.onkeyup, true);
    }

    document.addEventListener('mousedown', this.onmousedown);

    this.$('input').trigger('focus');
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.closeWatcher?.destroy();
    document.removeEventListener('keyup', this.onkeyup, true);
    document.removeEventListener('mousedown', this.onmousedown);
  }

  onkeyup = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
      e.stopPropagation();
      this.attrs.onclose();
    }
  };

  onmousedown = (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // The button that opened the picker toggles it itself.
    if (!this.element.contains(target) && !target.closest('.IconInput-toggle')) this.attrs.onclose();
  };

  setStyle(style: IconStyle) {
    this.style = style;

    if (style === 'regular' && !this.regular) {
      regularFontAwesomeIcons().then((icons) => {
        this.regular = icons;
        m.redraw();
      });
    }
  }

  icons(): FontAwesomeIcon[] | null {
    if (this.style === 'regular') return this.regular;

    return fontAwesomeIcons()[this.style === 'brands' ? 'brands' : 'classic'];
  }

  view() {
    const icons = this.icons();
    const words = this.query.toLowerCase().split(/\s+/).filter(Boolean);
    const matches = icons ? icons.filter((icon) => words.every((word) => icon.search.includes(word))) : [];
    const prefix = PREFIX[this.style];

    return (
      <div className="IconPicker" role="dialog" aria-label={app.translator.trans('core.lib.icon_picker.label', {}, true)}>
        <div className="IconPicker-bar">
          <input
            className="FormControl"
            type="search"
            placeholder={app.translator.trans('core.lib.icon_picker.search_placeholder', {}, true)}
            value={this.query}
            oninput={(e: InputEvent) => (this.query = (e.target as HTMLInputElement).value)}
          />
          <div className="ButtonGroup IconPicker-styles">
            {(Object.keys(PREFIX) as IconStyle[]).map((style) => (
              <Button
                className={classList('Button', { active: this.style === style })}
                aria-pressed={this.style === style ? 'true' : 'false'}
                onclick={() => this.setStyle(style)}
              >
                {app.translator.trans(`core.lib.icon_picker.${style}_label`)}
              </Button>
            ))}
          </div>
        </div>

        {icons ? (
          <div className="IconPicker-grid">
            {matches.slice(0, SHOWN).map((icon) => {
              const value = `${prefix} fa-${icon.name}`;

              return (
                <button
                  type="button"
                  className={classList('IconPicker-icon', { active: this.attrs.value === value })}
                  title={value}
                  aria-label={value}
                  onclick={() => this.attrs.onpick(value)}
                >
                  <Icon name={value} />
                </button>
              );
            })}
          </div>
        ) : (
          <LoadingIndicator display="block" size="small" />
        )}

        <div className="IconPicker-footer">{icons ? this.footer(matches.length) : null}</div>
      </div>
    );
  }

  footer(count: number) {
    if (!count) return app.translator.trans('core.lib.icon_picker.no_results_text');
    if (count > SHOWN) return app.translator.trans('core.lib.icon_picker.more_text', { shown: SHOWN, count });

    return app.translator.trans('core.lib.icon_picker.count_text', { count });
  }
}
