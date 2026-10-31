import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import Icon from '../../common/components/Icon';
import classList from '../../common/utils/classList';

export interface ISettingsSectionAttrs extends ComponentAttrs {
  label: Mithril.Children;
  description?: Mithril.Children;
  icon?: string;
  id?: string;
}

/**
 * A titled group of settings on an admin page.
 *
 * On wide screens the heading and description sit in a column beside the
 * fields; on narrow screens they stack above them.
 *
 * @example
 * <SettingsSection label="Notifications" description="When we email your members." icon="fas fa-bell">
 *   {this.buildSettingComponent({ setting: 'acme.notify', type: 'bool', label: 'Notify on reply' })}
 * </SettingsSection>
 */
export default class SettingsSection<CustomAttrs extends ISettingsSectionAttrs = ISettingsSectionAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { className, label, description, icon, id, ...attrs } = this.attrs;
    const headingId = id ? `${id}-heading` : undefined;

    return (
      <section className={classList(this.classNames(), className)} id={id} aria-labelledby={headingId} {...attrs}>
        <header className="SettingsSection-header">
          <h3 className="SettingsSection-title" id={headingId}>
            {icon ? <Icon name={icon} className="SettingsSection-icon" /> : null}
            {label}
          </h3>
          {description ? <p className="SettingsSection-description helpText">{description}</p> : null}
        </header>
        <div className={this.bodyClassNames()}>{vnode.children}</div>
      </section>
    );
  }

  classNames(): string {
    return 'SettingsSection';
  }

  bodyClassNames(): string {
    return 'SettingsSection-body';
  }
}
