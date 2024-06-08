import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';

export interface IThemeModeAttrs extends ComponentAttrs {
  label: string;
  mode: string;
  selected?: boolean;
  alternate?: boolean;
}

export default class ThemeMode<CustomAttrs extends IThemeModeAttrs = IThemeModeAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { mode, selected, className, alternate, label, ...attrs } = vnode.attrs;

    return (
      <label
        className={classList('ThemeMode', className, `ThemeMode--${mode}`, { 'ThemeMode--active': selected, 'ThemeMode--switch': alternate })}
        {...attrs}
      >
        <div className="ThemeMode-container" data-theme={mode === 'auto' ? 'light' : mode}>
          <div className="ThemeMode-preview">
            <div className="ThemeMode-header"></div>
            <div className="ThemeMode-hero"></div>
            <div className="ThemeMode-main">
              <div className="ThemeMode-sidebar">
                <div className="ThemeMode-startDiscussion"></div>
                <div className="ThemeMode-items">
                  {Array.from({ length: 3 }).map((_, i) => (
                    <div className="ThemeMode-sidebar-line">
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
                    <div className="ThemeMode-content-item">
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
