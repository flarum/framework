import username from './helpers/username';
import type { Dayjs } from 'dayjs';
import User from './models/User';
import extract from './utils/extract';
import formatMessage, { Translation } from 'format-message';
import { fireDeprecationWarning } from './helpers/fireDebugWarning';
import extractText from './utils/extractText';
import ItemList from './utils/ItemList';

type Translations = { [key: string]: string | Translation };
type TranslatorParameters = Record<string, unknown>;
type DateTimeFormatCallback = (id?: string) => string | void;

export default class Translator {
  /**
   * A map of translation keys to their translated values.
   */
  get translations(): Translations {
    return this.formatter.setup().translations[this.getLocale()] ?? {};
  }

  /**
   * A item list of date time format callbacks.
   */
  dateTimeFormats: ItemList<DateTimeFormatCallback> = new ItemList();

  /**
   * The underlying ICU MessageFormatter util.
   */
  protected formatter = formatMessage;

  /**
   * Sets the formatter's locale to the provided value.
   */
  setLocale(locale: string) {
    this.formatter.setup({
      locale,
      translations: {
        [locale]: this.formatter.setup().translations.tmp ?? {},
      },
    });
  }

  /**
   * Returns the formatter's current locale.
   */
  getLocale(): string {
    return (Array.isArray(this.formatter.setup().locale) ? this.formatter.setup().locale[0] : this.formatter.setup().locale) as string;
  }

  addTranslations(translations: Translations) {
    this.formatter.setup({
      translations: {
        // The locale has not been set yet by this time.
        // @TODO: in v2.0 we should add translations with the locale info.
        tmp: Object.assign(this.translations, translations),
      },
    });
  }

  /**
   * An extensible entrypoint for extenders to register type handlers for translations.
   */
  protected formatterTypeHandlers() {
    return this.formatter;
  }

  /**
   * A temporary system to preprocess parameters.
   * Should not be used by extensions.
   * TODO: An extender will be added in v1.x.
   *
   * @internal
   */
  protected preprocessParameters(parameters: TranslatorParameters, translation: string | Translation) {
    // If we've been given a user model as one of the input parameters, then
    // we'll extract the username and use that for the translation. In the
    // future there should be a hook here to inspect the user and change the
    // translation key. This will allow a gender property to determine which
    // translation key is used.

    if ('user' in parameters) {
      const user = extract(parameters, 'user') as User;

      if (!parameters.username) parameters.username = username(user);
    }

    // To maintain backwards compatibility, we will catch HTML elements and
    // push the tags as mithril children to the parameters keyed by the tag name.
    // Will be removed in v2.0
    translation = typeof translation === 'string' ? translation : translation.message;
    const elements = translation.match(/<(\w+)[^>]*>.*?<\/\1>/g);
    const tags = elements?.map((element) => element.match(/^<(\w+)/)![1]) || [];

    for (const tag of tags) {
      if (!parameters[tag]) {
        fireDeprecationWarning(
          `Any HTML tags used within translations must have corresponding mithril component parameters.\nCaught in translation: \n\n"""\n${translation}\n"""`,
          '',
          'v2.0',
          'flarum/framework'
        );

        parameters[tag] = ({ children }: any) => m(tag, children);
      }
    }

    // The old formatter allowed rich parameters as such:
    // { link: <Link href="https://flarum.org"/> }
    // The new formatter dictates that the rich parameter must be a function,
    // like so: { link: ({ children }) => <Link href="https://flarum.org">{children}</Link> }
    // This layer allows the old format to be used, and converts it to the new format.
    for (const key in parameters) {
      const value: any = parameters[key];

      if (tags.includes(key) && typeof value === 'object' && value.attrs && value.tag) {
        parameters[key] = ({ children }: any) => {
          return m(value.tag, value.attrs, children);
        };
      }
    }

    return parameters;
  }

  trans(id: string, parameters: TranslatorParameters): NestedStringArray;
  trans(id: string, parameters: TranslatorParameters, extract: false): NestedStringArray;
  trans(id: string, parameters: TranslatorParameters, extract: true): string;
  trans(id: string): NestedStringArray | string;
  trans(id: string, parameters: TranslatorParameters = {}, extract = false) {
    const translation = this.preprocessTranslation(this.translations[id]);

    if (translation) {
      parameters = this.preprocessParameters(parameters, translation);
      this.translations[id] = translation;

      const locale = this.formatter.rich({ id, default: id }, parameters);

      if (extract) return extractText(locale);

      return locale;
    }

    return id;
  }

  /**
   * Formats the time.
   *
   * The format of the time will be chosen by the following order:
   * - Custom format defined in the item list.
   * - The format defined in current locale.
   * - DayJS default format.
   */
  formatDateTime(time: Dayjs, id: string): string {
    const formatCallback = this.dateTimeFormats.has(id) && this.dateTimeFormats.get(id);

    if (formatCallback) {
      const result = formatCallback.apply(this, [id]);
      if (result) return result;
    }

    return time.format(this.translations[id]);
  }

  /**
   * Backwards compatibility for translations such as `<a href='{href}'>`, the old
   * formatter supported that, but the new one doesn't, so attributes are auto dropped
   * to avoid errors.
   *
   * @deprecated Will be removed in v2.0
   * @private
   */
  private preprocessTranslation(translation: string | Translation | undefined): string | undefined {
    if (!translation) return;

    translation = typeof translation === 'string' ? translation : translation.message;

    // If the translation contains a <x ...attrs> tag, then we'll need to
    // remove the attributes for backwards compatibility. Will be removed in v2.0.
    // And if it did have attributes, then we'll fire a warning
    if (translation.match(/<\w+ [^>]+>/g)) {
      fireDeprecationWarning(
        `Any HTML tags used within translations must be simple tags, without attributes.\nCaught in translation: \n\n"""\n${translation}\n"""`,
        '',
        'v2.0',
        'flarum/framework'
      );
    }

    return translation.replace(/<(\w+)([^>]*)>/g, '<$1>');
  }
}
