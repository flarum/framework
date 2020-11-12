import DashboardWidget from './DashboardWidget';
import getCategorizedExtensions from '../utils/getCategorizedExtensions';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';

export default class ExtensionsWidget extends DashboardWidget {
  className() {
    return 'ExtensionsWidget';
  }

  content() {
    const categorizedExtensions = getCategorizedExtensions();
    const categories = app.extensionCategories;

    return (
      <div className="ExtensionsWidget-list">
        <div className="container">
          {Object.keys(categories).map((category) => {
            if (categorizedExtensions[category]) {
              return (
                <div className="ExtensionList-Category">
                  <h4
                    className="ExtensionList-Label">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>
                  <ul className="ExtensionList">
                    {Object.keys(categorizedExtensions[category]).map((id) => {
                      const extension = app.data.extensions[id];

                      return (
                        <li className={'ExtensionListItem ' + (!this.isEnabled(extension.id) ? 'disabled' : '')}>
                          <Link href={app.route('extension', {id: extension.id})}>
                            <div className="ExtensionListItem-content">
                            <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                              {extension.icon ? icon(extension.icon.name) : ''}
                            </span>
                              <span
                                className="ExtensionListItem-title">{extension.extra['flarum-extension'].title}</span>
                            </div>
                          </Link>
                        </li>
                      );
                    })}
                  </ul>
                </div>
              );
            }
          })}
        </div>
      </div>
    );
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
