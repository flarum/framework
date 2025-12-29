import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import getCategorizedExtensions from '../utils/getCategorizedExtensions';
import Link from '../../common/components/Link';
import classList from '../../common/utils/classList';
import Icon from '../../common/components/Icon';

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
        {Object.keys(categories).map((category) => !!this.categorizedExtensions[category] && this.extensionCategory(category))}
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
    const isAbandoned = extension.abandoned;
    const hasReplacement = typeof isAbandoned === 'string';

    return (
      <li className={classList('ExtensionListItem', { disabled: !isExtensionEnabled(extension.id), abandoned: isAbandoned })}>
        <Link href={app.route('extension', { id: extension.id })}>
          <div className="ExtensionListItem-content">
            <div className="ExtensionListItem-icon-wrapper">
              <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                {!!extension.icon && <Icon name={extension.icon.name} />}
              </span>
              {isAbandoned && (
                <span className={classList('ExtensionListItem-badge Badge', hasReplacement ? 'Badge--danger' : 'Badge--warning')}>
                  <Icon name="fas fa-exclamation-triangle" />
                </span>
              )}
            </div>
            <span className="ExtensionListItem-title">{extension.extra['flarum-extension'].title}</span>
          </div>
        </Link>
      </li>
    );
  }
}
