import { MessageFormatter, pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import username from './helpers/username';
import extract from './utils/extract';

export default class Translator {
  constructor(locale) {
    /**
     * A map of translation keys to their translated values.
     *
     * @type {Object}
     * @public
     */
    this.translations = {};

    this.formatter = new MessageFormatter(null, this.formatterTypeHandlers());
  }

  formatterTypeHandlers() {
    return {
      plural: pluralTypeHandler,
      select: selectTypeHandler,
    };
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

  setLocale(locale) {
    this.formatter.locale = locale;
  }

  addTranslations(translations) {
    Object.assign(this.translations, translations);
  }

  trans(id, parameters) {
    const translation = this.translations[id];

    if (translation) {
      parameters = this.preprocessParameters(parameters || {});

      console.log(translation, parameters);
      console.log(this.formatter.format(translation, parameters));

      return this.apply(translation, parameters);
    }

    return id;
  }

  /**
   * @deprecated, remove before stable
   */
  transChoice(id, number, parameters) {
    return this.trans(id, parameters);
  }

  apply(translation, input) {
    translation = translation.split(new RegExp('({[a-z0-9_]+}|</?[a-z0-9_]+>)', 'gi'));

    const hydrated = [];
    const open = [hydrated];

    translation.forEach((part) => {
      const match = part.match(new RegExp('{([a-z0-9_]+)}|<(/?)([a-z0-9_]+)>', 'i'));

      if (match) {
        // Either an opening or closing tag.
        if (match[1]) {
          open[0].push(input[match[1]]);
        } else if (match[3]) {
          if (match[2]) {
            // Closing tag. We start by removing all raw children (generally in the form of strings) from the temporary
            // holding array, then run them through m.fragment to convert them to vnodes. Usually this will just give us a
            // text vnode, but using m.fragment as opposed to an explicit conversion should be more flexible. This is necessary because
            // otherwise, our generated vnode will have raw strings as its children, and mithril expects vnodes.
            // Finally, we add the now-processed vnodes back onto the holding array (which is the same object in memory as the
            // children array of the vnode we are currently processing), and remove the reference to the holding array so that
            // further text will be added to the full set of returned elements.
            const rawChildren = open[0].splice(0, open[0].length);
            open[0].push(...m.fragment(rawChildren).children);
            open.shift();
          } else {
            // If a vnode with a matching tag was provided in the translator input, we use that. Otherwise, we create a new vnode
            // with this tag, and an empty children array (since we're expecting to insert children, as that's the point of having this in translator)
            let tag = input[match[3]] || { tag: match[3], children: [] };
            open[0].push(tag);
            // Insert the tag's children array as the first element of open, so that text in between the opening
            // and closing tags will be added to the tag's children, not to the full set of returned elements.
            open.unshift(tag.children || tag);
          }
        }
      } else {
        // Not an html tag, we add it to open[0], which is either the full set of returned elements (vnodes and text),
        // or if an html tag is currently being processed, the children attribute of that html tag's vnode.
        open[0].push(part);
      }
    });

    return hydrated.filter((part) => part);
  }
}
