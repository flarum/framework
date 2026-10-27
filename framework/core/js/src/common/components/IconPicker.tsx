import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import Icon from './Icon';
import LoadingIndicator from './LoadingIndicator';
import Select from './Select';
import classList from '../utils/classList';
import fontAwesomeIcons, {
  fetchUnreadableStylesheets,
  fontAwesomeStyles,
  fontAwesomeIconsFor,
  type FontAwesomeIcon,
  type FontAwesomeStyle,
} from '../utils/fontAwesomeIcons';
import type Mithril from 'mithril';

export interface IIconPickerAttrs extends ComponentAttrs {
  /** The class currently chosen, e.g. `fas fa-bolt`. */
  value?: string;
  /**
   * Shown before the search box, such as the button that opened the picker,
   * so the picker can stand in for the field it belongs to.
   */
  toggle?: Mithril.Children;
  /** Called with the full class of the icon picked. */
  onpick: (icon: string) => void;
  /**
   * Called when the picker should close: on Escape, or a click outside it.
   * Only Escape should move focus back to the field.
   */
  onclose: (reason: 'escape' | 'outside') => void;
}

// The long classes that select a family or a weight. A value may use them
// instead of the short class (`fa-sharp fa-light` for `fasl`).
const FAMILY_CLASSES: Record<string, string> = {
  'fa-classic': 'classic',
  'fa-duotone': 'duotone',
  'fa-sharp': 'sharp',
  'fa-sharp-duotone': 'sharp-duotone',
  'fa-brands': 'brands',
};

const WEIGHT_CLASSES: Record<string, number> = { 'fa-solid': 900, 'fa-regular': 400, 'fa-light': 300, 'fa-thin': 100 };

const WEIGHT_NAMES: Record<number, string> = { 900: 'solid', 400: 'regular', 300: 'light', 100: 'thin' };

const KNOWN_FAMILIES = ['classic', 'duotone', 'sharp', 'sharp-duotone', 'brands'];

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
  /** Whether the forum's stylesheets are still being fetched to read them. */
  reading = false;
  styles: FontAwesomeStyle[] = [];
  style?: FontAwesomeStyle;
  /** The icons of each style shown so far, by its class. */
  loaded = new Map<string, FontAwesomeIcon[]>();
  closeWatcher?: CloseWatcher;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const fetching = fetchUnreadableStylesheets(app.forum?.attribute<string>('assetsBaseUrl'));

    if (fetching) {
      this.reading = true;
      fetching.then(() => {
        this.reading = false;
        this.readStyles();
        m.redraw();
      });
    } else {
      this.readStyles();
    }
  }

  readStyles() {
    this.styles = fontAwesomeStyles();
    this.setStyle(this.styleOf(this.attrs.value || '') ?? this.styles[0]);
  }

  /**
   * The style a value is drawn in, from its short class (`fasl`) or its long
   * ones (`fa-sharp fa-light`, where classic and solid are the defaults).
   */
  styleOf(value: string): FontAwesomeStyle | undefined {
    const classes = value.split(/\s+/);
    const short = this.styles.find((style) => classes.includes(style.prefix));

    if (short) return short;

    const family = classes.map((name) => FAMILY_CLASSES[name]).find(Boolean) ?? 'classic';
    const weight = classes.map((name) => WEIGHT_CLASSES[name]).find(Boolean) ?? (family === 'brands' ? 400 : 900);

    return this.styles.find((style) => style.family === family && style.weight === weight);
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

  setStyle(style: FontAwesomeStyle | undefined) {
    this.style = style;

    if (!style || this.loaded.has(style.prefix)) return;

    const icons = fontAwesomeIconsFor(style);

    if (Array.isArray(icons)) {
      this.loaded.set(style.prefix, icons);
    } else {
      icons.then((list) => {
        this.loaded.set(style.prefix, list);
        m.redraw();
      });
    }
  }

  /**
   * Whether an icon is the current value, however the value is written:
   * `fasl fa-house` and `fa-sharp fa-light fa-house` are the same icon.
   */
  isCurrent(icon: FontAwesomeIcon): boolean {
    const value = this.attrs.value || '';
    const name = value.split(/\s+/).find((word) => word.startsWith('fa-') && !(word in FAMILY_CLASSES) && !(word in WEIGHT_CLASSES));

    return name === `fa-${icon.name}` && this.styleOf(value) === this.style;
  }

  /**
   * Show another family, at the weight shown now where the family has it.
   */
  setFamily(family: string) {
    const weights = this.styles.filter((style) => style.family === family);

    this.setStyle(weights.find((style) => style.weight === this.style?.weight) ?? weights[0]);
  }

  icons(): FontAwesomeIcon[] | null {
    return (this.style && this.loaded.get(this.style.prefix)) || null;
  }

  familyLabel(family: string): string {
    if (KNOWN_FAMILIES.includes(family)) return app.translator.trans(`core.lib.icon_picker.families.${family.replace('-', '_')}`, {}, true);

    // A family added after this was written, such as one of Font Awesome's
    // newer Pro+ families: its own name is the best label there is.
    return family.replace(/(^|-)([a-z])/g, (match, dash, letter) => (dash ? ' ' : '') + letter.toUpperCase());
  }

  weightLabel(weight: number): string {
    return WEIGHT_NAMES[weight] ? app.translator.trans(`core.lib.icon_picker.weights.${WEIGHT_NAMES[weight]}`, {}, true) : String(weight);
  }

  view() {
    const icons = this.icons();
    const matches = this.matches(icons);
    const name = this.searchWords().join('-');
    const exact = name ? matches.find((icon) => icon.name === name) : undefined;
    const typedClass = this.typedClass();

    // No icon could be read from any stylesheet, even fetched again: say why,
    // rather than showing an empty list as though nothing matched.
    const unreadable = !this.reading && !fontAwesomeIcons().classic.length && !fontAwesomeIcons().brands.length;
    const prefix = this.style?.prefix ?? 'fas';
    const families = [...new Set(this.styles.map((style) => style.family))];
    const weights = this.styles.filter((style) => style.family === this.style?.family);

    // Every icon is drawn in this style instead, set on the Advanced page. The
    // grid still shows each style as itself, so it can be chosen.
    // Brands are never restyled, so there it doesn't apply.
    const forcedStyle = this.style?.family !== 'brands' && app.forum?.attribute<string | null>('fontAwesomeForcedStyle');

    return (
      <div className="IconPicker" role="dialog" aria-label={app.translator.trans('core.lib.icon_picker.label', {}, true)}>
        <div className="IconPicker-bar">
          {this.attrs.toggle}
          <input
            className="FormControl"
            type="search"
            placeholder={app.translator.trans('core.lib.icon_picker.search_placeholder', {}, true)}
            aria-label={app.translator.trans('core.lib.icon_picker.search_placeholder', {}, true)}
            value={this.query}
            oninput={(e: InputEvent) => this.search((e.target as HTMLInputElement).value)}
            onkeydown={(e: KeyboardEvent) => {
              // The picker usually sits inside a modal's form: Enter picks
              // rather than submitting it.
              if (e.key !== 'Enter') return;

              e.preventDefault();
              this.pickFromSearch();
            }}
          />
        </div>

        <div className="IconPicker-panel">
          {(families.length > 1 || weights.length > 1) && (
            <div className="IconPicker-styles">
              {families.length > 1 && (
                <Select
                  className="IconPicker-family"
                  aria-label={app.translator.trans('core.lib.icon_picker.family_label', {}, true)}
                  options={Object.fromEntries(families.map((family) => [family, this.familyLabel(family)]))}
                  value={this.style?.family}
                  onchange={(family: string) => this.setFamily(family)}
                />
              )}
              {weights.length > 1 && (
                <div className="ButtonGroup IconPicker-weights">
                  {weights.map((style) => (
                    <Button
                      className={classList('Button', { active: this.style === style })}
                      aria-pressed={this.style === style ? 'true' : 'false'}
                      onclick={() => this.setStyle(style)}
                    >
                      {this.weightLabel(style.weight)}
                    </Button>
                  ))}
                </div>
              )}
            </div>
          )}

          {forcedStyle && <p className="IconPicker-forced helpText">{app.translator.trans('core.lib.icon_picker.forced_style_text')}</p>}

          {unreadable ? (
            <p className="IconPicker-unreadable helpText">
              {app.translator.trans('core.lib.icon_picker.unreadable_text', { example: <code>fas fa-house</code> })}
            </p>
          ) : icons ? (
            <div className="IconPicker-grid">
              {matches.slice(0, SHOWN).map((icon) => {
                const value = `${prefix} fa-${icon.name}`;

                return (
                  <button
                    type="button"
                    className={classList('IconPicker-icon', { active: this.isCurrent(icon) })}
                    title={value}
                    aria-label={value}
                    onclick={() => this.attrs.onpick(value)}
                  >
                    <Icon name={value} noStyleOverride />
                  </button>
                );
              })}
            </div>
          ) : (
            <LoadingIndicator display="block" size="small" />
          )}

          <div className="IconPicker-footer">
            <span className="IconPicker-count">{icons && !unreadable ? this.footer(matches.length) : null}</span>
            {typedClass && !exact && (
              <Button className="Button Button--link IconPicker-use" onclick={() => this.attrs.onpick(typedClass)}>
                {app.translator.trans('core.lib.icon_picker.use_button', { value: <code>{typedClass}</code> })}
              </Button>
            )}
            {!!this.attrs.value && (
              <Button className="Button Button--link IconPicker-remove" onclick={() => this.attrs.onpick('')}>
                {app.translator.trans('core.lib.icon_picker.remove_button')}
              </Button>
            )}
          </div>
        </div>
      </div>
    );
  }

  /**
   * Pick for Enter: an icon named exactly, then a typed class nothing matches,
   * then the closest match. Worked out from the search as it is now, as Enter
   * can come before the redraw that follows typing, such as after a paste.
   */
  pickFromSearch() {
    const matches = this.matches(this.icons());
    const name = this.searchWords().join('-');
    const exact = name ? matches.find((icon) => icon.name === name) : undefined;
    const typedClass = this.typedClass();
    const prefix = this.style?.prefix ?? 'fas';

    if (exact) this.attrs.onpick(`${prefix} fa-${exact.name}`);
    else if (typedClass && !matches.length) this.attrs.onpick(typedClass);
    else if (matches[0]) this.attrs.onpick(`${prefix} fa-${matches[0].name}`);
  }

  /**
   * The words of the search that name an icon: "fas fa-house",
   * "fa-sharp fa-light fa-house" and "fa-house" all search for "house".
   */
  searchWords(): string[] {
    const styleWords = new Set(['fa', ...this.styles.map((style) => style.prefix), ...Object.keys(FAMILY_CLASSES), ...Object.keys(WEIGHT_CLASSES)]);

    return this.query
      .toLowerCase()
      .split(/\s+/)
      .filter((word) => word && !styleWords.has(word))
      .map((word) => word.replace(/^fa-/, ''));
  }

  /**
   * The search as a class to use as it is, when it is written like one
   * (`fa-kit fa-my-icon`): a Kit's own icons and other custom classes aren't
   * in the list, but can still be chosen.
   */
  typedClass(): string | null {
    const words = this.query.trim().split(/\s+/);
    const looksLikeClass = words.some((word) => word.startsWith('fa-') && !(word in FAMILY_CLASSES) && !(word in WEIGHT_CLASSES));

    return looksLikeClass ? words.join(' ') : null;
  }

  /**
   * Search for a typed class in the style it names, so Enter picks
   * `fasl fa-house` in Sharp Light, not in the style shown before.
   */
  search(query: string) {
    this.query = query;

    const classes = query.toLowerCase().split(/\s+/);
    const namesStyle = classes.some((word) => this.styles.some((style) => style.prefix === word) || word in FAMILY_CLASSES || word in WEIGHT_CLASSES);
    const style = namesStyle ? this.styleOf(classes.join(' ')) : undefined;

    if (style && style !== this.style) this.setStyle(style);
  }

  matches(icons: FontAwesomeIcon[] | null): FontAwesomeIcon[] {
    if (!icons) return [];

    const words = this.searchWords();
    const found = icons.filter((icon) => words.every((word) => icon.search.includes(word)));

    // Enter picks the first result, so the closest names lead: an exact
    // match, then names that start with the search.
    const query = words.join('-');
    const rank = (icon: FontAwesomeIcon) => (icon.name === query ? 0 : icon.name.startsWith(query) ? 1 : 2);

    if (query) return found.sort((a, b) => rank(a) - rank(b));

    // Not searching: the current icon leads, so it can be seen and kept.
    const current = found.findIndex((icon) => this.isCurrent(icon));

    return current > 0 ? [found[current], ...found.slice(0, current), ...found.slice(current + 1)] : found;
  }

  footer(count: number) {
    if (!count) return app.translator.trans('core.lib.icon_picker.no_results_text');
    if (count > SHOWN) return app.translator.trans('core.lib.icon_picker.more_text', { shown: SHOWN, count });

    return app.translator.trans('core.lib.icon_picker.count_text', { count });
  }
}
