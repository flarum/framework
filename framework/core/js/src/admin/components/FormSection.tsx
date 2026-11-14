import SettingsSection from './SettingsSection';
import type { ISettingsSectionAttrs } from './SettingsSection';

export interface IFormSectionAttrs extends ISettingsSectionAttrs {}

/**
 * A titled group of settings.
 *
 * Renders as a `SettingsSection`, keeping the `FormSection` class for existing styles.
 * New code can use `SettingsSection` directly.
 */
export default class FormSection<CustomAttrs extends IFormSectionAttrs = IFormSectionAttrs> extends SettingsSection<CustomAttrs> {
  classNames(): string {
    return 'SettingsSection FormSection';
  }

  bodyClassNames(): string {
    return 'SettingsSection-body FormSection-body';
  }
}
