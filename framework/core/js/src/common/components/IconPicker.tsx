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
  /**
   * Called when the picker should close: on Escape, or a click outside it.
   * Only Escape should move focus back to the field.
   */
  onclose: (reason: 'escape' | 'outside') => void;
}

type IconStyle = 'solid' | 'regular' | 'brands';

const PREFIX: Record<IconStyle, string> = { solid: 'fas', regular: 'far', brands: 'fab' };

const LABEL: Record<IconStyle, string> = {
  solid: 'core.lib.icon_picker.solid_label',
  regular: 'core.lib.icon_picker.regular_label',
  brands: 'core.lib.icon_picker.brands_label',
};

// Words a search can contain that name a style, not an icon.
const STYLE_WORDS = new Set(['fa', 'fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands']);

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
        this.attrs.onclose('escape');
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
      this.attrs.onclose('escape');
    }
  };

  onmousedown = (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    // The field this picker belongs to — its own toggle included — handles
    // its own clicks. Any other click closes it, another picker's toggle too.
    if (!(this.element.parentElement ?? this.element).contains(target)) this.attrs.onclose('outside');
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
    const matches = this.matches(icons);
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
            onkeydown={(e: KeyboardEvent) => {
              // The picker usually sits inside a modal's form: Enter picks the
              // first match rather than submitting it.
              if (e.key !== 'Enter') return;

              e.preventDefault();
              if (matches[0]) this.attrs.onpick(`${prefix} fa-${matches[0].name}`);
            }}
          />
          <div className="ButtonGroup IconPicker-styles">
            {(Object.keys(PREFIX) as IconStyle[]).map((style) => (
              <Button
                className={classList('Button', { active: this.style === style })}
                aria-pressed={this.style === style ? 'true' : 'false'}
                onclick={() => this.setStyle(style)}
              >
                {app.translator.trans(LABEL[style])}
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

  matches(icons: FontAwesomeIcon[] | null): FontAwesomeIcon[] {
    if (!icons) return [];

    // "fas fa-house" and "fa-house" find the house as well as "house" does.
    const words = this.query
      .toLowerCase()
      .split(/\s+/)
      .filter((word) => word && !STYLE_WORDS.has(word))
      .map((word) => word.replace(/^fa-/, ''));

    const found = icons.filter((icon) => words.every((word) => icon.search.includes(word)));

    // Enter picks the first result, so the closest names lead: an exact
    // match, then names that start with the search.
    const query = words.join('-');
    const rank = (icon: FontAwesomeIcon) => (icon.name === query ? 0 : icon.name.startsWith(query) ? 1 : 2);

    return query ? found.sort((a, b) => rank(a) - rank(b)) : found;
  }

  footer(count: number) {
    if (!count) return app.translator.trans('core.lib.icon_picker.no_results_text');
    if (count > SHOWN) return app.translator.trans('core.lib.icon_picker.more_text', { shown: SHOWN, count });

    return app.translator.trans('core.lib.icon_picker.count_text', { count });
  }
}
