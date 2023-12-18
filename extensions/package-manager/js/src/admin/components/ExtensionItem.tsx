import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import classList from 'flarum/common/utils/classList';
import icon from 'flarum/common/helpers/icon';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { Extension } from 'flarum/admin/AdminApplication';

import { UpdatedPackage } from '../states/ControlSectionState';
import WhyNotModal from './WhyNotModal';
import Label from './Label';
import Dropdown from 'flarum/common/components/Dropdown';

export interface ExtensionItemAttrs extends ComponentAttrs {
  extension: Extension;
  updates: UpdatedPackage;
  onClickUpdate:
    | CallableFunction
    | {
        soft: CallableFunction;
        hard: CallableFunction;
      };
  whyNotWarning?: boolean;
  isCore?: boolean;
  updatable?: boolean;
  isDanger?: boolean;
}

export default class ExtensionItem<Attrs extends ExtensionItemAttrs = ExtensionItemAttrs> extends Component<Attrs> {
  view(vnode: Mithril.Vnode<Attrs, this>): Mithril.Children {
    const { extension, updates, onClickUpdate, whyNotWarning, isCore, isDanger } = this.attrs;
    const latestVersion = updates['latest-minor'] ?? (updates['latest-major'] && !isCore ? updates['latest-major'] : null);

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
            <span className="PackageManager-extension-version-current">{this.version(updates['version'])}</span>
            {latestVersion ? (
              <Label className="PackageManager-extension-version-latest" type={updates['latest-minor'] ? 'success' : 'warning'}>
                {this.version(latestVersion)}
              </Label>
            ) : null}
          </div>
        </div>
        <div className="PackageManager-extension-controls">
          {onClickUpdate && typeof onClickUpdate === 'function' ? (
            <Tooltip text={app.translator.trans('flarum-package-manager.admin.extensions.update')}>
              <Button
                icon="fas fa-arrow-alt-circle-up"
                className="Button Button--icon Button--flat"
                onclick={onClickUpdate}
                aria-label={app.translator.trans('flarum-package-manager.admin.extensions.update')}
              />
            </Tooltip>
          ) : onClickUpdate ? (
            <Dropdown
              buttonClassName="Button Button--icon Button--flat"
              icon="fas fa-arrow-alt-circle-up"
              label={app.translator.trans('flarum-package-manager.admin.extensions.update')}
            >
              <Button icon="fas fa-arrow-alt-circle-up" className="Button" onclick={onClickUpdate.soft}>
                {app.translator.trans('flarum-package-manager.admin.extensions.update_soft_label')}
              </Button>
              <Button icon="fas fa-arrow-alt-circle-up" className="Button" onclick={onClickUpdate.hard} disabled={!updates['direct-dependency']}>
                {app.translator.trans('flarum-package-manager.admin.extensions.update_hard_label')}
              </Button>
            </Dropdown>
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

  version(v: string): string {
    return v.charAt(0) === 'v' ? v.substring(1) : v;
  }
}
