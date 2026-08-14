import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import SelectDropdown from '../../common/components/SelectDropdown';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import ThemeMode, { ColorScheme } from '../../common/components/ThemeMode';

const DEFAULT_ICON = 'fa-solid fa-circle-half-stroke';

export default class ThemeSwitcher<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends Component<CustomAttrs> {
  iconItems(): ItemList<string> {
    const items = new ItemList<string>();

    items.add(ColorScheme.Light, 'fa-regular fa-sun');
    items.add(ColorScheme.LightHighContrast, 'fa-solid fa-sun');
    items.add(ColorScheme.Dark, 'fa-regular fa-moon');
    items.add(ColorScheme.DarkHighContrast, 'fa-solid fa-moon');

    return items;
  }

  icon(scheme: string): string {
    const items = this.iconItems();

    return items.has(scheme) ? items.get(scheme) : DEFAULT_ICON;
  }

  active(): string {
    return app.colorScheme ?? app.session.user?.preferences()?.colorScheme ?? app.forum.attribute<string>('colorScheme') ?? ColorScheme.Auto;
  }

  label(scheme: string): string {
    const custom = ThemeMode.colorSchemes.find((s) => s.id === scheme)?.label;

    return custom || app.translator.trans('core.forum.settings.color_schemes.' + scheme.replace('-', '_') + '_mode_label', {}, true);
  }

  select(scheme: string): void {
    app.setColorScheme(scheme);

    if (app.session.user) {
      app.session.user.savePreferences({ colorScheme: scheme });
    } else {
      sessionStorage.setItem('colorScheme', scheme);
    }

    m.redraw();
  }

  view(): Mithril.Children {
    const active = this.active();
    const resolved = active === ColorScheme.Auto ? app.getSystemColorSchemePreference() : active;

    return (
      <SelectDropdown
        className="ThemeSwitcher HeaderDropdown"
        buttonClassName="Button Button--flat"
        icon={this.icon(resolved)}
        noStyleOverride
        defaultLabel={this.label(active)}
        accessibleToggleLabel={app.translator.trans('core.forum.header.theme_switcher_accessible_label')}
      >
        {ThemeMode.colorSchemes.map((scheme) => (
          <Button
            className={`ThemeSwitcher-option ThemeSwitcher-option--${scheme.id}`}
            icon={active === scheme.id ? 'fa-solid fa-check' : this.icon(scheme.id)}
            noStyleOverride
            active={active === scheme.id}
            onclick={() => this.select(scheme.id)}
          >
            {this.label(scheme.id)}
          </Button>
        ))}
      </SelectDropdown>
    );
  }
}
