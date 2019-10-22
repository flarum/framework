import extract from './utils/extract';
import extractText from './utils/extractText';
import username from './helpers/username';

type Translations = { [key: string]: string };

export default class Translator {
    translations: Translations = {};
    locale = null;

    constructor() {
        /**
         * A map of translation keys to their translated values.
         *
         * @type {Object}
         * @public
         */
        this.translations = {};

        this.locale = null;
    }

    addTranslations(translations) {
        Object.assign(this.translations, translations);
    }

    trans(id: string, parameters = null) {
        const translation = this.translations[id];

        if (translation) {
            return this.apply(translation, parameters || {});
        }

        return id;
    }

    transText(id: string, parameters = null) {
      return extractText(this.trans(id, parameters));
    }

    apply(translation: string, input: any) {
        if ('user' in input) {
            const user = extract(input, 'user');
            if (!input.username) input.username = username(user);
        }

        const parts = translation.split(new RegExp('({[a-z0-9_]+}|</?[a-z0-9_]+>)', 'gi'));

        const hydrated: any[] = [];
        const open: any[][] = [hydrated];

        parts.forEach(part => {
            const match = part.match(new RegExp('{([a-z0-9_]+)}|<(/?)([a-z0-9_]+)>', 'i'));

            if (match) {
                if (match[1]) {
                    open[0].push(input[match[1]]);
                } else if (match[3]) {
                    if (match[2]) {
                        open.shift();
                    } else {
                        let tag = input[match[3]] || { tag: match[3], children: [] };
                        open[0].push(tag);
                        open.unshift(tag.children || tag);
                    }
                }
            } else {
                open[0].push(part);
            }
        });

        return hydrated.filter(part => part);
    }
}
