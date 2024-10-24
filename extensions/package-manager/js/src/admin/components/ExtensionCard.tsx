import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import Badge from 'flarum/common/components/Badge';
import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import formatAmount from 'flarum/common/utils/formatAmount';
import { type Extension as ExtensionInfo } from 'flarum/admin/AdminApplication';
import ExternalExtension from '../models/ExternalExtension';
import { UpdatedPackage } from '../states/ControlSectionState';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import classList from 'flarum/common/utils/classList';
import Label from './Label';
import Tooltip from 'flarum/common/components/Tooltip';
import Dropdown from 'flarum/common/components/Dropdown';
import WhyNotModal from './WhyNotModal';
import LinkButton from 'flarum/common/components/LinkButton';

export type CommonExtension = ExternalExtension | ExtensionInfo;

export interface IExtensionAttrs extends ComponentAttrs {
  extension: CommonExtension;
  updates?: UpdatedPackage;
  onClickUpdate?:
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

export default class ExtensionCard<CustomAttrs extends IExtensionAttrs = IExtensionAttrs> extends Component<CustomAttrs> {
  getExtension() {
    return this.attrs.extension instanceof ExternalExtension ? this.attrs.extension.toLocalExtension() : this.attrs.extension;
  }

  view() {
    const extension = this.getExtension();
    const { isCore, isDanger } = this.attrs;

    return (
      <div
        className={classList('ExtensionCard', {
          'ExtensionCard--core': isCore,
          'ExtensionCard--danger': isDanger,
        })}
      >
        <div className="ExtensionCard-header">
          {this.icon()}
          <Tooltip text={extension.name}>
            <h4>{extension.extra['flarum-extension'].title}</h4>
          </Tooltip>
          {this.attrs.extension instanceof ExternalExtension && <div className="ExtensionCard-badges">{this.badges().toArray()}</div>}
          <div className="ExtensionCard-actions">{this.actionItems().toArray()}</div>
        </div>
        <div className="ExtensionCard-body">
          <p>{extension.description}</p>
        </div>
        <div className="ExtensionCard-footer">
          <div className="ExtensionCard-meta">{this.metaItems().toArray()}</div>
        </div>
      </div>
    );
  }

  icon() {
    const extension = this.getExtension();

    if (this.attrs.extension instanceof ExternalExtension && extension.id in app.data.extensions) {
      extension.icon = app.data.extensions[extension.id].icon;
    }

    const style: any = extension.icon || {};

    if (
      !extension.icon?.name &&
      this.attrs.extension instanceof ExternalExtension &&
      !(extension.id in app.data.extensions) &&
      this.attrs.extension.iconUrl()
    ) {
      style.backgroundImage = `url(${this.attrs.extension.iconUrl()})`;
    }

    return (
      <span className="ExtensionIcon" style={extension.icon}>
        {extension.icon?.name ? <Icon name={extension.icon.name} /> : null}
      </span>
    );
  }

  badges() {
    const items = new ItemList<Mithril.Children>();

    const extension = this.attrs.extension as ExternalExtension;

    if (extension.isSupported()) {
      items.add(
        'compatible',
        <Badge
          icon="fas fa-check"
          type="success"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.compatible')}
          className="Badge--flat Badge--square"
        />
      );
    } else {
      items.add(
        'incompatible',
        <Badge
          icon="fas fa-times"
          type="danger"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.incompatible')}
          className="Badge--flat Badge--square"
        />
      );
    }

    if (extension.isPremium()) {
      items.add(
        'premium',
        <Badge
          icon="fas fa-dollar-sign"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.premium')}
          className="ExtensionCard-badge--premium Badge--flat Badge--square"
        />
      );
    }

    if (!extension.isProductionReady()) {
      items.add(
        'unstable',
        <Badge
          icon="fas fa-flask"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.unstable')}
          className="Badge--flat Badge--square Badge--danger"
        />
      );
    }

    if (extension.name().split('/')[0] === 'fof') {
      items.add(
        'fof',
        <Badge
          icon="fas fa-users"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.fof')}
          className="Badge--flat Badge--square"
        />
      );
    }

    if (extension.name().split('/')[0] === 'flarum') {
      items.add(
        'flarum',
        <Badge
          icon="fab fa-flarum"
          label={app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.badges.flarum')}
          className="ExtensionCard-badge--flarum Badge--flat Badge--square"
        />
      );
    }

    return items;
  }

  metaItems() {
    const items = new ItemList<Mithril.Children>();

    const { updates, isCore } = this.attrs;
    const latestVersion = updates ? updates['latest-minor'] ?? (updates['latest-major'] && !isCore ? updates['latest-major'] : null) : null;

    if (this.attrs.extension instanceof ExternalExtension) {
      items.add(
        'downloads',
        <span>
          <Icon name="fas fa-circle-down" />
          {app.translator.trans('flarum-extension-manager.admin.sections.discover.extension.downloads', {
            count: this.attrs.extension.downloads(),
            formattedCount: formatAmount(this.attrs.extension.downloads()),
          })}
        </span>
      );
    } else {
      items.add(
        'version',
        <div className="ExtensionCard-version">
          <span className="ExtensionCard-version-current">{this.version(updates!['version'])}</span>
          {latestVersion ? (
            <>
              <Icon name="fas fa-arrow-right" />
              <Label className="ExtensionCard-version-latest" type={updates!['latest-minor'] ? 'success' : 'warning'}>
                {this.version(latestVersion)}
              </Label>
            </>
          ) : null}
        </div>
      );
    }

    if (this.attrs.extension instanceof ExternalExtension) {
      items.add('version', <div className="ExtensionCard-version">v{this.version(this.attrs.extension.highestVersion())}</div>);

      items.add(
        'link',
        <LinkButton
          className="Button Button--ua-reset Button--link Button--icon"
          href={this.attrs.extension.httpUri()}
          target="_blank"
          icon="fas fa-external-link-alt"
          external={true}
        />
      );
    }

    return items;
  }

  actionItems() {
    const items = new ItemList<Mithril.Children>();

    const { updates, extension, onClickUpdate, whyNotWarning } = this.attrs;

    if (extension instanceof ExternalExtension) {
      if (!(extension.extensionId() in app.data.extensions)) {
        items.add(
          'install',
          <Button
            className="Button Button--icon Button--flat"
            icon="fas fa-cloud-arrow-down"
            onclick={() => {
              app.extensionManager.control.requirePackage({ package: extension.name() });
            }}
          />
        );
      } else {
        items.add('installed', <Button className="Button Button--icon Button--flat Button--success" icon="fas fa-check-circle" disabled={true} />);
      }
    } else {
      if (onClickUpdate && typeof onClickUpdate === 'function') {
        items.add(
          'update',
          <Tooltip text={app.translator.trans('flarum-extension-manager.admin.extensions.update')}>
            <Button
              icon="fas fa-cloud-arrow-down"
              className="Button Button--icon Button--flat"
              onclick={onClickUpdate}
              aria-label={app.translator.trans('flarum-extension-manager.admin.extensions.update')}
            />
          </Tooltip>
        );
      } else if (onClickUpdate) {
        items.add(
          'update',
          <Dropdown
            buttonClassName="Button Button--icon Button--flat"
            icon="fas fa-ellipsis"
            label={app.translator.trans('flarum-extension-manager.admin.extensions.update')}
          >
            <Button icon="fas fa-cloud-arrow-down" onclick={onClickUpdate.soft}>
              {app.translator.trans('flarum-extension-manager.admin.extensions.update_soft_label')}
            </Button>
            <Button icon="fas fa-rotate" onclick={onClickUpdate.hard} disabled={!updates!['direct-dependency']}>
              {app.translator.trans('flarum-extension-manager.admin.extensions.update_hard_label')}
            </Button>
          </Dropdown>
        );
      }

      if (whyNotWarning)
        items.add(
          'whyNot',
          <Tooltip text={app.translator.trans('flarum-extension-manager.admin.extensions.check_why_it_failed_updating')}>
            <Button
              icon="fas fa-exclamation-circle"
              className="Button Button--icon Button--flat Button--danger"
              onclick={() => app.modal.show(WhyNotModal, { package: extension.name })}
              aria-label={app.translator.trans('flarum-extension-manager.admin.extensions.check_why_it_failed_updating')}
            />
          </Tooltip>
        );
    }

    return items;
  }

  version(v: string): string {
    return v.charAt(0) === 'v' ? v.substring(1) : v;
  }
}
