import Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import classList from 'flarum/common/utils/classList';
import icon from 'flarum/common/helpers/icon';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { Extension as BaseExtension } from 'flarum/admin/AdminApplication';
import { UpdatedPackage } from './Updater';
import WhyNotModal from './WhyNotModal';

/*
 * @todo fix in core
 */
export type Extension = BaseExtension & {
  name: string;
};

export interface ExtensionItemAttrs extends ComponentAttrs {
  extension: Extension;
  updates: UpdatedPackage;
  onClickUpdate: CallableFunction;
  whyNotWarning?: boolean;
  isCore?: boolean;
  updatable?: boolean;
  isDanger?: boolean;
}

export default class ExtensionItem<Attrs extends ExtensionItemAttrs = ExtensionItemAttrs> extends Component<Attrs> {
  view(vnode: Mithril.Vnode<Attrs, this>): Mithril.Children {
    const { extension, updates, onClickUpdate, whyNotWarning, isCore, isDanger } = this.attrs;

    return (
      <div
        className={classList({
          'PackageManager-extension': true,
          'PackageManager-extension--core': isCore,
          'PackageManager-extension--danger': isDanger,
        })}
      >
        <div className="PackageManager-extension-icon ExtensionIcon" style={extension.icon}>
          {extension.icon ? icon(extension.icon.name) : ''}
        </div>
        <div className="PackageManager-extension-info">
          <div className="PackageManager-extension-name">{extension.extra['flarum-extension'].title}</div>
          <div className="PackageManager-extension-version">
            <span className="PackageManager-extension-version-current">{this.version(extension.version)}</span>
            {updates['latest-minor'] ? (
              <span className="PackageManager-extension-version-latest PackageManager-extension-version-latest--minor">
                {this.version(updates['latest-minor']!)}
              </span>
            ) : null}
            {updates['latest-major'] && !isCore ? (
              <span className="PackageManager-extension-version-latest PackageManager-extension-version-latest--major">
                {this.version(updates['latest-major']!)}
              </span>
            ) : null}
          </div>
        </div>
        <div className="PackageManager-extension-controls">
          {onClickUpdate ? (
            <Tooltip text={app.translator.trans('flarum-package-manager.admin.extensions.update')}>
              <Button
                icon="fas fa-arrow-alt-circle-up"
                className="Button Button--icon Button--flat"
                onclick={onClickUpdate}
                aria-label={app.translator.trans('flarum-package-manager.admin.extensions.update')}
              />
            </Tooltip>
          ) : null}
          {whyNotWarning ? (
            <Tooltip text={app.translator.trans('flarum-package-manager.admin.extensions.check_why_it_failed_updating')}>
              <Button
                icon="fas fa-exclamation-circle"
                className="Button Button--icon Button--flat Button--danger"
                onclick={() => app.modal.show(WhyNotModal, { package: extension.name })}
                aria-label={app.translator.trans('flarum-package-manager.admin.extensions.check_why_it_failed_updating')}
              />
            </Tooltip>
          ) : null}
        </div>
      </div>
    );
  }

  private version(v: string): string {
    return 'v' + v.replace('v', '');
  }
}
