import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';

export interface IThemeModeAttrs extends ComponentAttrs {
  label: string;
  mode: string;
  selected?: boolean;
  alternate?: boolean;
}

export enum ColorScheme {
  Auto = 'auto',
  Light = 'light',
  Dark = 'dark',
  LightHighContrast = 'light-hc',
  DarkHighContrast = 'dark-hc',
}

export type ColorSchemeData = {
  id: ColorScheme | string;
  label?: string | null;
};

export default class ThemeMode<CustomAttrs extends IThemeModeAttrs = IThemeModeAttrs> extends Component<CustomAttrs> {
  static colorSchemes: ColorSchemeData[] = [
    { id: ColorScheme.Auto },
    { id: ColorScheme.Light },
    { id: ColorScheme.Dark },
    { id: ColorScheme.LightHighContrast },
    { id: ColorScheme.DarkHighContrast },
  ];

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { mode, selected, className, alternate, label, ...attrs } = vnode.attrs;

    return (
      <label
        className={classList('ThemeMode', className, `ThemeMode--${mode}`, { 'ThemeMode--active': selected, 'ThemeMode--switch': alternate })}
        {...attrs}
      >
        <div
          className="ThemeMode-container"
          data-theme={mode === 'auto' ? 'light' : mode}
          data-colored-header={document.documentElement.getAttribute('data-colored-header')}
        >
          <div className="ThemeMode-preview">
            <div className="ThemeMode-header">
              <div className="ThemeMode-header-text"></div>
              <div className="ThemeMode-header-icon"></div>
              <div className="ThemeMode-header-icon"></div>
            </div>
            <div className="ThemeMode-hero">
              <div className="ThemeMode-hero-title"></div>
              <div className="ThemeMode-hero-desc"></div>
            </div>
            <div className="ThemeMode-main">
              <div className="ThemeMode-sidebar">
                <div className="ThemeMode-startDiscussion">
                  <div className="ThemeMode-startDiscussion-text"></div>
                </div>
                <div className="ThemeMode-items">
                  {Array.from({ length: 3 }).map((_, i) => (
                    <div className="ThemeMode-sidebar-line" key={i}>
                      <div className="ThemeMode-sidebar-icon"></div>
                      <div className="ThemeMode-sidebar-text"></div>
                    </div>
                  ))}
                </div>
              </div>
              <div className="ThemeMode-content">
                <div className="ThemeMode-toolbar">
                  <div className="ThemeMode-button"></div>
                  <div className="ThemeMode-button"></div>
                </div>
                <div className="ThemeMode-items">
                  {Array.from({ length: 3 }).map((_, i) => (
                    <div className="ThemeMode-content-item" key={i}>
                      <div className="ThemeMode-content-item-author"></div>
                      <div className="ThemeMode-content-item-meta">
                        <div className="ThemeMode-content-item-title"></div>
                        <div className="ThemeMode-content-item-excerpt"></div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
          {mode === 'auto' ? <ThemeMode mode={mode === 'auto' ? 'dark' : null} alternate={true} selected={selected} {...attrs} /> : null}
        </div>
        {!alternate ? <div className="ThemeMode-legend">{label}</div> : null}
      </label>
    );
  }
}
