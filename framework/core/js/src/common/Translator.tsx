import type { Dayjs } from 'dayjs';
import { RichMessageFormatter, mithrilRichHandler, NestedStringArray } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import username from './helpers/username';
import User from './models/User';
import extract from './utils/extract';
import extractText from './utils/extractText';
import ItemList from './utils/ItemList';

type Translations = Record<string, string>;
type TranslatorParameters = Record<string, unknown>;
type DateTimeFormatCallback = (id?: string) => string | void;

export default class Translator {
  /**
   * A map of translation keys to their translated values.
   */
  translations: Translations = {};

  /**
   * A item list of date time format callbacks.
   */
  dateTimeFormats: ItemList<DateTimeFormatCallback> = new ItemList();

  /**
   * The underlying ICU MessageFormatter util.
   */
  protected formatter = new RichMessageFormatter(null, this.formatterTypeHandlers(), mithrilRichHandler);

  /**
   * Sets the formatter's locale to the provided value.
   */
  setLocale(locale: string) {
    this.formatter.locale = locale;
  }

  /**
   * Returns the formatter's current locale.
   */
  getLocale() {
    return this.formatter.locale;
  }

  addTranslations(translations: Translations) {
    Object.assign(this.translations, translations);
  }

  /**
   * An extensible entrypoint for extenders to register type handlers for translations.
   */
  protected formatterTypeHandlers() {
    return {
      plural: pluralTypeHandler,
      select: selectTypeHandler,
    };
  }

  /**
   * A temporary system to preprocess parameters.
   * Should not be used by extensions.
   * TODO: An extender will be added in v1.x.
   *
   * @internal
   */
  protected preprocessParameters(parameters: TranslatorParameters) {
    // If we've been given a user model as one of the input parameters, then
    // we'll extract the username and use that for the translation. In the
    // future there should be a hook here to inspect the user and change the
    // translation key. This will allow a gender property to determine which
    // translation key is used.

    if ('user' in parameters) {
      const user = extract(parameters, 'user') as User;

      if (!parameters.username) parameters.username = username(user);
    }

    return parameters;
  }

  trans(id: string, parameters: TranslatorParameters): NestedStringArray;
  trans(id: string, parameters: TranslatorParameters, extract: false): NestedStringArray;
  trans(id: string, parameters: TranslatorParameters, extract: true): string;
  trans(id: string): NestedStringArray | string;
  trans(id: string, parameters: TranslatorParameters = {}, extract = false) {
    const translation = this.translations[id];

    if (translation) {
      parameters = this.preprocessParameters(parameters);
      const locale = this.formatter.rich(translation, parameters);

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
}
