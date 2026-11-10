import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import Link from '../../common/components/Link';
import Icon from '../../common/components/Icon';

/**
 * Mirrors Extension::nameToId() from PHP.
 * "flarum/mentions"      → "flarum-mentions"
 * "flarum/flarum-ext-suspend" → "flarum-suspend"
 * "some-vendor/foo"      → "some-vendor-foo"
 */
function packageNameToExtensionId(packageName) {
  const [vendor, pkg] = packageName.split('/');
  const stripped = pkg.replace(/^flarum-ext-/, '').replace(/^flarum-/, '');
  return `${vendor}-${stripped}`;
}

export default class ExtensionsWidget extends DashboardWidget {
  className() {
    return 'ExtensionsWidget';
  }

  content() {
    const abandoned = this.abandonedExtensions();
    const suggested = this.suggestedExtensions();
    const disabled = this.disabledExtensions();

    return [
      <h3 className="ExtensionsWidget-title">
        <Icon name="fas fa-puzzle-piece" />
        {app.translator.trans('core.admin.extensions-health-widget.title')}
      </h3>,
      <div className="ExtensionsWidget-list">
        {this.renderSection('abandoned', abandoned, this.renderAbandonedItem.bind(this))}
        {this.renderSection('suggested', suggested, this.renderSuggestedItem.bind(this))}
        {this.renderDisabledSection(disabled)}
      </div>,
    ];
  }

  renderSection(type, items, renderItem) {
    const isHealthSection = type === 'abandoned' || type === 'suggested';

    return (
      <div className={`ExtensionsWidget-section ExtensionsWidget-section--${type}${items.length ? ' ExtensionsWidget-section--has-items' : ''}`}>
        <h4 className="ExtensionsWidget-sectionHeading">
          {app.translator.trans(`core.admin.extensions-health-widget.section_${type}_heading`)}
          {!!items.length && <span className="ExtensionsWidget-sectionCount">{items.length}</span>}
        </h4>
        <p className="ExtensionsWidget-sectionHelp">{app.translator.trans(`core.admin.extensions-health-widget.section_${type}_help`)}</p>
        {items.length ? (
          <ul className="ExtensionsWidget-itemList">{items.map(renderItem)}</ul>
        ) : (
          <p className={`ExtensionsWidget-sectionEmpty${isHealthSection ? ' ExtensionsWidget-sectionEmpty--ok' : ''}`}>
            {isHealthSection && <Icon name="fas fa-check-circle" className="ExtensionsWidget-okIcon" />}
            {app.translator.trans(`core.admin.extensions-health-widget.section_${type}_empty`)}
          </p>
        )}
      </div>
    );
  }

  renderDisabledSection(extensions) {
    return (
      <div className="ExtensionsWidget-section ExtensionsWidget-section--disabled">
        <h4 className="ExtensionsWidget-sectionHeading">
          {app.translator.trans('core.admin.extensions-health-widget.section_disabled_heading')}
          {!!extensions.length && <span className="ExtensionsWidget-sectionCount">{extensions.length}</span>}
        </h4>
        <p className="ExtensionsWidget-sectionHelp">{app.translator.trans('core.admin.extensions-health-widget.section_disabled_help')}</p>
        {extensions.length ? (
          <ul className="ExtensionsWidget-disabledGrid">{extensions.map(this.renderDisabledItem.bind(this))}</ul>
        ) : (
          <p className="ExtensionsWidget-sectionEmpty">{app.translator.trans('core.admin.extensions-health-widget.section_disabled_empty')}</p>
        )}
      </div>
    );
  }

  renderAbandonedItem(item) {
    const { extension } = item;
    const hasReplacement = typeof extension.abandoned === 'string';
    const title = extension.extra['flarum-extension'].title;

    return (
      <li className={`ExtensionsWidget-item ExtensionsWidget-item--${hasReplacement ? 'danger' : 'warning'}`}>
        <Link className="ExtensionsWidget-itemLink" href={app.route('extension', { id: extension.id })}>
          <span className="ExtensionIcon ExtensionsWidget-itemIcon" style={extension.icon}>
            {!!extension.icon && <Icon name={extension.icon.name} />}
          </span>
          <span className="ExtensionsWidget-itemBody">
            <span className="ExtensionsWidget-itemName">
              {title}
              <Icon name={`fas fa-${hasReplacement ? 'exclamation-circle' : 'exclamation-triangle'}`} className="ExtensionsWidget-itemIndicator" />
            </span>
            <span className="ExtensionsWidget-itemDetail">
              {hasReplacement
                ? app.translator.trans('core.admin.extensions-health-widget.abandoned_with_replacement', {
                    replacement: <code>{extension.abandoned}</code>,
                  })
                : app.translator.trans('core.admin.extensions-health-widget.abandoned_no_replacement')}
            </span>
          </span>
        </Link>
      </li>
    );
  }

  renderSuggestedItem(item) {
    const { packageId, description, suggestedBy } = item;
    const packagistUrl = `https://packagist.org/packages/${packageId}`;

    return (
      <li className="ExtensionsWidget-item ExtensionsWidget-item--info">
        <a className="ExtensionsWidget-itemLink" href={packagistUrl} target="_blank" rel="noopener noreferrer">
          <span className="ExtensionsWidget-itemBody">
            <span className="ExtensionsWidget-itemName">
              <code>{packageId}</code>
              <Icon name="fas fa-external-link-alt" className="ExtensionsWidget-externalIcon" />
            </span>
            <span className="ExtensionsWidget-itemDetail">
              {app.translator.trans('core.admin.extensions-health-widget.suggested_by', {
                extension: <strong>{suggestedBy}</strong>,
              })}
              {description ? ` — ${description}` : ''}
            </span>
          </span>
        </a>
      </li>
    );
  }

  renderDisabledItem(extension) {
    const title = extension.extra['flarum-extension'].title;

    return (
      <li className="ExtensionsWidget-disabledItem">
        <Link href={app.route('extension', { id: extension.id })}>
          <span className="ExtensionIcon ExtensionsWidget-disabledIcon" style={extension.icon}>
            {!!extension.icon && <Icon name={extension.icon.name} />}
          </span>
          <span className="ExtensionsWidget-disabledName">{title}</span>
        </Link>
      </li>
    );
  }

  abandonedExtensions() {
    return Object.values(app.data.extensions)
      .filter((ext) => ext.abandoned)
      .map((extension) => ({ extension }));
  }

  suggestedExtensions() {
    // Authoritative list of all installed composer packages (including non-extension libraries)
    const installedPackageNames = new Set(app.data.installedPackages ?? []);
    const suggestions = [];
    const seen = new Set();

    for (const ext of Object.values(app.data.extensions)) {
      if (!ext.suggest) continue;

      for (const [packageId, description] of Object.entries(ext.suggest)) {
        // Only surface vendor/package style entries (skip PHP ext-* etc.)
        if (!packageId.includes('/')) continue;
        if (seen.has(packageId)) continue;

        // Skip if already installed (as any composer package)
        if (installedPackageNames.has(packageId)) continue;

        seen.add(packageId);
        suggestions.push({
          packageId,
          description,
          suggestedBy: ext.extra['flarum-extension'].title,
        });
      }
    }

    return suggestions;
  }

  disabledExtensions() {
    return Object.values(app.data.extensions).filter((ext) => !isExtensionEnabled(ext.id) && !ext.abandoned);
  }
}
