import { RichMessageFormatter } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import username from './helpers/username';
import extract from './utils/extract';

export default class Translator {
  constructor() {
    /**
     * A map of translation keys to their translated values.
     *
     * @type {Object}
     * @public
     */
    this.translations = {};

    this.formatter = new RichMessageFormatter(null, this.formatterTypeHandlers(), fillTags);
  }

  formatterTypeHandlers() {
    return {
      plural: pluralTypeHandler,
      select: selectTypeHandler,
    };
  }

  setLocale(locale) {
    this.formatter.locale = locale;
  }

  addTranslations(translations) {
    Object.assign(this.translations, translations);
  }

  preprocessParameters(parameters) {
    // If we've been given a user model as one of the input parameters, then
    // we'll extract the username and use that for the translation. In the
    // future there should be a hook here to inspect the user and change the
    // translation key. This will allow a gender property to determine which
    // translation key is used.
    if ('user' in parameters) {
      const user = extract(parameters, 'user');

      if (!parameters.username) parameters.username = username(user);
    }
    return parameters;
  }

  trans(id, parameters) {
    const translation = this.translations[id];

    if (translation) {
      parameters = this.preprocessParameters(parameters || {});
      return this.formatter.rich(translation, parameters);
    }

    return id;
  }

  /**
   * @deprecated, remove before stable
   */
  transChoice(id, number, parameters) {
    return this.trans(id, parameters);
  }
}
