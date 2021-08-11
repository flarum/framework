import Mithril from 'mithril';
import Link from '../../common/components/Link';
import classList from '../../common/utils/classList';
import ItemList from '../../common/utils/ItemList';
import app from '../app';

import AdminPage from './AdminPage';

export interface IAdvancedPageAttrs extends Mithril.Attributes {}

export interface ICreateDriverComponentOptions<Options extends string[]> {
  /**
   * The default driver value.
   *
   * This will appear selected if the driver is not specified.
   */
  defaultValue: Options[number];
  /**
   * Custom class to apply to the `<select>` component.
   *
   * This is applied in addition to the default `AdvancedPage-driverSelect` class.
   */
  className: string;
}

export default class AdvancedPage extends AdminPage {
  oninit(vnode: Mithril.Vnode<IAdvancedPageAttrs, this>) {
    super.oninit(vnode);
  }

  headerInfo() {
    return {
      className: 'AdvancedPage',
      icon: 'fas fa-rocket',
      title: app.translator.trans('core.admin.advanced.title'),
      description: app.translator.trans('core.admin.advanced.description'),
    };
  }

  content() {
    return (
      <>
        <form class="Form">{this.items().toArray()}</form>
      </>
    );
  }

  items(): ItemList {
    const items = new ItemList();

    if (!app.data.settings.advanced_settings_pane_enabled) {
      items.add(
        'page_not_enabled',
        // TODO: Add link to docs page
        <p class="AdvancedPage-notEnabledWarning">
          {app.translator.trans('core.admin.advanced.not_enabled_warning', {
            a: <Link external href="https://docs.flarum.org/" />,
            icon: <span aria-label={app.translator.trans('core.admin.advanced.warning_icon_accessible_label')} class="fas fa-exclamation-triangle" />,
          })}
        </p>,
        110
      );
    } else {
      items.add(
        'large_community_text',
        // TODO: Add link to docs page
        <p class="AdvancedPage-congratsText">
          {app.translator.trans('core.admin.advanced.large_community_note', {
            a: <Link external href="https://docs.flarum.org/" />,
            icon: <span aria-label={app.translator.trans('core.admin.advanced.info_icon_accessible_label')} class="fas fa-info-circle" />,
          })}
        </p>,
        110
      );
    }

    items.add(
      'drivers',
      <fieldset class="Form-group AdvancedPage-category">
        <legend>{app.translator.trans('core.admin.advanced.drivers.legend')}</legend>

        {this.drivers().toArray()}
      </fieldset>,
      90
    );

    items.add('save', this.submitButton(), -10);

    return items;
  }

  drivers(): ItemList {
    const items = new ItemList();

    items.add(
      'queueDriver',
      this.createDriverComponent('queue_driver', 'core.admin.advanced.drivers.queue', app.data.queueDrivers, {
        className: 'AdvancedPage-queueDriver',
        defaultValue: 'database',
      }),
      100
    );

    return items;
  }

  /**
   * Build a form component for a given driver.
   *
   * Requires the follow translations under the given prefix:
   * - `driver_heading` (shown as legend for the form group)
   * - `driver_label` (shown as the label for the select box)
   * - `names.{driver_id}` (shown as the options for the select box)
   *
   * @param settingKey The setting key for the driver.
   * @param driverTranslatorPrefix The prefix used for translations.
   * @param driverOptions An array of possible driver values.
   * @param options Optional settings for the component.
   *
   * @example <caption>Queue driver</caption>
   *          this.createDriverComponent(
   *            'queue_driver',
   *            'core.admin.advanced.drivers.queue',
   *            [ 'database', 'sync' ],
   *            },
   *            {
   *              defaultValue: 'database',
   *            },
   *          );
   */
  createDriverComponent<Options extends string[]>(
    settingKey: string,
    driverTranslatorPrefix: string,
    driverOptions: Options,
    options: Partial<ICreateDriverComponentOptions<Options>> = {}
  ): JSX.Element {
    return (
      <fieldset class="Form-group">
        <legend>{app.translator.trans(`${driverTranslatorPrefix}.driver_heading`)}</legend>

        {this.buildSettingComponent({
          type: 'select',
          setting: settingKey,
          options: driverOptions.reduce(
            (acc, value) => ({
              ...acc,
              [value]: app.translator.trans(`${driverTranslatorPrefix}.names.${value}`),
            }),
            {} as Record<Options[number], ReturnType<typeof app.translator.trans>>
          ),
          default: options.defaultValue,
          label: app.translator.trans(`${driverTranslatorPrefix}.driver_label`),
          className: classList('AdvancedPage-driverSelect', options.className),
        })}
      </fieldset>
    );
  }
}
