import { RichMessageFormatter, mithrilRichHandler } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import username from './helpers/username';
import User from './models/User';
import extract from './utils/extract';

type Translations = Record<string, string>;
type TranslatorParameters = Record<string, unknown>;

export default class Translator {
  /**
   * A map of translation keys to their translated values.
   */
  translations: Translations = {};

  /**
   * The underlying ICU MessageFormatter util.
   */
  protected formatter = new RichMessageFormatter(null, this.formatterTypeHandlers(), mithrilRichHandler);

  setLocale(locale: string) {
    this.formatter.locale = locale;
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

  trans(id: string, parameters: TranslatorParameters = {}) {
    const translation = this.translations[id];

    if (translation) {
      parameters = this.preprocessParameters(parameters);
      return this.formatter.rich(translation, parameters);
    }

    return id;
  }
}
