import DashboardWidget from './DashboardWidget';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import getCategorizedExtensions from '../utils/getCategorizedExtensions';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';

export default class ExtensionsWidget extends DashboardWidget {
  oninit(vnode) {
    super.oninit(vnode);

    this.categorizedExtensions = getCategorizedExtensions();
  }

  className() {
    return 'ExtensionsWidget';
  }

  content() {
    const categories = app.extensionCategories;

    return (
      <div className="ExtensionsWidget-list">
        {Object.keys(categories).map((category) => (this.categorizedExtensions[category] ? this.extensionCategory(category) : ''))}
      </div>
    );
  }

  extensionCategory(category) {
    return (
      <div className="ExtensionList-Category">
        <h4 className="ExtensionList-Label">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>
        <ul className="ExtensionList">{this.categorizedExtensions[category].map((extension) => this.extensionWidget(extension))}</ul>
      </div>
    );
  }

  extensionWidget(extension) {
    return (
      <li className={'ExtensionListItem ' + (!isExtensionEnabled(extension.id) ? 'disabled' : '')}>
        <Link href={app.route('extension', { id: extension.id })}>
          <div className="ExtensionListItem-content">
            <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
              {extension.icon ? icon(extension.icon.name) : ''}
            </span>
            <span className="ExtensionListItem-title">{extension.extra['flarum-extension'].title}</span>
          </div>
        </Link>
      </li>
    );
  }
}
