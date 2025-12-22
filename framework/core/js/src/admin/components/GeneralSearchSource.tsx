import type Mithril from 'mithril';

import app from '../app';
import highlight from '../../common/helpers/highlight';
import type { GlobalSearchSource } from './GlobalSearch';
import extractText from '../../common/utils/extractText';
import Link from '../../common/components/Link';
import Icon from '../../common/components/Icon';
import PermissionGrid from './PermissionGrid';
import escapeRegExp from '../../common/utils/escapeRegExp';
import { GeneralIndexData, GeneralIndexItem } from '../states/GeneralSearchIndex';
import { ExtensionConfig, SettingConfigInput } from '../utils/AdminRegistry';
import ItemList from '../../common/utils/ItemList';

export class GeneralSearchResult {
  constructor(
    public id: string,
    public category: string,
    public icon: { name: string; [key: string]: any },
    public tree: string[],
    public link: string,
    public help?: string
  ) {}
}

/**
 * Finds and displays settings, permissions and installed extensions (i.e. general search results) in the search dropdown.
 */
export default class GeneralSearchSource implements GlobalSearchSource {
  protected results = new Map<string, GeneralSearchResult[]>();

  public resource: string = 'general';

  title(): string {
    return extractText(app.translator.trans('core.admin.search_source.general.heading'));
  }

  isCached(query: string): boolean {
    return this.results.has(query.toLowerCase());
  }

  async search(query: string, limit: number): Promise<void> {
    query = query.toLowerCase();

    return new Promise((resolve) => {
      const results: GeneralSearchResult[] = [];

      // extensions.
      for (const extensionId in app.data.extensions) {
        const extension = app.data.extensions[extensionId];
        const title = extension.extra['flarum-extension'].title || extensionId.replace('core-', '');
        const icon = extension.icon || { name: 'fas fa-cog' };
        const category = extension.extra['flarum-extension'].category || 'other';

        if (this.itemHasQuery(title, query)) {
          results.push(
            new GeneralSearchResult(
              extensionId,
              app.translator.trans('core.admin.nav.categories.' + category, {}, true),
              icon,
              [title],
              app.route('extension', { id: extensionId })
            )
          );
        }
      }

      // extension registered settings && permissions
      results.push(...this.lookup(app.registry.getData(), query));

      // manually registered settings && permissions into the search index.
      results.push(...this.lookup(app.generalIndex.getData(), query));

      this.results.set(query, results);
      m.redraw();

      resolve();
    });
  }

  protected lookup(
    data:
      | GeneralIndexData
      | {
          [key: string]: ExtensionConfig | undefined;
        },
    query: string
  ): GeneralSearchResult[] {
    const extensions = app.data.extensions;
    const permissionItems = PermissionGrid.prototype.permissionItems();

    const results: GeneralSearchResult[] = [];

    for (const extensionId in data) {
      // settings
      const settings = data[extensionId]!.settings;
      let normalizedSettings: GeneralIndexItem[] | SettingConfigInput[] = [];

      if (settings instanceof ItemList) {
        normalizedSettings = settings?.toArray();
      } else if (settings) {
        normalizedSettings = settings;
      }

      for (const setting of normalizedSettings) {
        if ('visible' in setting && !setting.visible()) {
          continue;
        }

        const label = 'label' in setting ? extractText(setting.label) : '';
        const help = 'help' in setting ? extractText(setting.help) : '';
        const corePage = !extensions[extensionId] ? extensionId.replace('core-', '') : null;
        const group = app.generalIndex.getGroup(extensionId);

        if (this.itemHasQuery(label, query) || this.itemHasQuery(help, query)) {
          const id = extensionId + '-' + ('setting' in setting ? setting : 'id' in setting ? setting.id : '');

          results.push(
            new GeneralSearchResult(
              id,
              group?.label ||
                extensions[extensionId]?.extra['flarum-extension'].title ||
                app.translator.trans('core.admin.' + corePage + '.title', {}, true),
              group?.icon || extensions[extensionId]?.icon || { name: 'fas fa-cog' },
              'tree' in setting && setting.tree ? setting.tree.map(extractText).push(label) : [label],
              group?.link || (corePage ? app.route(corePage) : app.route('extension', { id: extensionId })),
              help
            )
          );
        }
      }

      // permissions
      const permissions = data[extensionId]!.permissions || {};

      for (const permissionType in permissions) {
        // @ts-ignore
        const permissionList = (permissions[permissionType] || []).toArray();

        for (const permission of permissionList) {
          const label = extractText(permission.label);

          if (this.itemHasQuery(label, query)) {
            const id = extensionId + '-' + permissionType + '-' + permission.permission;
            const corePage = !extensions[extensionId] ? extensionId.replace('core-', '') : null;
            const group = app.generalIndex.getGroup(extensionId);

            results.push(
              new GeneralSearchResult(
                id,
                group?.label ||
                  extensions[extensionId]?.extra['flarum-extension'].title ||
                  app.translator.trans('core.admin.' + corePage + '.title', {}, true),
                group?.icon || extensions[extensionId]?.icon || { name: 'fas fa-key' },
                [
                  app.translator.trans('core.admin.permissions.title', {}, true),
                  extractText(permissionItems.get(permissionType)?.label) || permissionType,
                  label,
                ],
                group?.link || (corePage ? app.route(corePage) : app.route('extension', { id: extensionId }))
              )
            );
          }
        }
      }
    }

    return results;
  }

  protected itemHasQuery(item: string, query: string): boolean {
    return query.split(' ').every((part) => item.toLowerCase().includes(part));
  }

  view(query: string): Array<Mithril.Vnode> {
    const results = (this.results.get(query) || []).slice(0, 30);
    const categories = Array.from(new Set([...results.map((r) => r.category)]));

    if (!categories.length) return [];

    return categories.map((category) => {
      return (
        <>
          <li className="GeneralSearchResult-group Dropdown-header">{category}</li>
          {results
            .filter((r) => r.category === category)
            .map((result) => {
              const phrase = escapeRegExp(query);
              const highlightRegExp = new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');

              const tree: any[] = result.tree.map((part) => {
                return highlight(part, highlightRegExp, undefined, true);
              });

              let help: any = result.help;

              if (help) {
                help = highlight(help, highlightRegExp, 100, true);
              }

              return (
                <li className="GeneralSearchResult" data-index={'general-' + result.id} data-id={result.id}>
                  <Link href={result.link}>
                    <div className="ExtensionIcon" style={result.icon}>
                      <Icon name={result.icon.name} />
                    </div>
                    <div className="GeneralSearchResult-info">
                      <div className="GeneralSearchResult-tree">
                        {tree.map((part, i) => {
                          return [
                            <span>{part}</span>,
                            i < tree.length - 1 ? (
                              <span className="GeneralSearchResult-tree-separator">
                                <Icon name="fas fa-arrow-right" />
                              </span>
                            ) : null,
                          ];
                        })}
                      </div>
                      {help ? <div className="GeneralSearchResult-help">{help}</div> : null}
                    </div>
                  </Link>
                </li>
              );
            })}
        </>
      );
    });
  }

  customGrouping(): boolean {
    return true;
  }

  fullPage(query: string): null {
    return null;
  }

  gotoItem(id: string): string | null {
    return null;
  }
}
