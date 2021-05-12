export default class AdminPage extends Page {
    settings: {} | undefined;
    loading: boolean | undefined;
    content(): string;
    submitButton(): JSX.Element;
    header(): JSX.Element;
    headerInfo(): {
        className: string;
        icon: string;
        title: string;
        description: string;
    };
    /**
     * buildSettingComponent takes a settings object and turns it into a component.
     * Depending on the type of input, you can set the type to 'bool', 'select', or
     * any standard <input> type. Any values inside the 'extra' object will be added
     * to the component as an attribute.
     *
     * Alternatively, you can pass a callback that will be executed in ExtensionPage's
     * context to include custom JSX elements.
     *
     * @example
     *
     * {
     *    setting: 'acme.checkbox',
     *    label: app.translator.trans('acme.admin.setting_label'),
     *    type: 'bool',
     *    help: app.translator.trans('acme.admin.setting_help'),
     *    className: 'Setting-item'
     * }
     *
     * @example
     *
     * {
     *    setting: 'acme.select',
     *    label: app.translator.trans('acme.admin.setting_label'),
     *    type: 'select',
     *    options: {
     *      'option1': 'Option 1 label',
     *      'option2': 'Option 2 label',
     *    },
     *    default: 'option1',
     * }
     *
     * @param setting
     * @returns {JSX.Element}
     */
    buildSettingComponent(entry: any): JSX.Element;
    onsaved(): void;
    setting(key: any, fallback?: string): any;
    dirty(): {};
    isChanged(): number;
    saveSettings(e: any): Promise<void>;
}
import Page from "../../common/components/Page";
