import DashboardWidget from './DashboardWidget';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';

export default class ExtensionsWidget extends DashboardWidget {
  className() {
    return 'Widget ExtensionsWidget';
  }

  content() {
    return (
      <div className="ExtensionsWidget-list">
        <div className="container">
          <h2 className="ExtensionsWidget-title">{app.translator.trans('core.admin.dashboard.extensions_title')}</h2>
          <ul className="ExtensionList">
            {Object.keys(app.data.extensions).map((id) => {
              const extension = app.data.extensions[id];

              return (
                <li className={'ExtensionListItem ' + (!this.isEnabled(extension.id) ? 'disabled' : '')}>
                  <Link href={app.route('extension', { id: extension.id })}>
                    <div className="ExtensionListItem-content">
                      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                        {extension.icon ? icon(extension.icon.name) : ''}
                      </span>
                    </div>
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
      </div>
    );
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
