import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
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
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    classNames(): string;
    bodyClassNames(): string;
}
