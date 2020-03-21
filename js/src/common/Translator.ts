import extract from './utils/extract';
import extractText from './utils/extractText';
import username from './helpers/username';

type Translations = { [key: string]: string };

export default class Translator {
    /**
     * A map of translation keys to their translated values.
     */
    translations: Translations = {};
    locale?: string;

    addTranslations(translations) {
        Object.assign(this.translations, translations);
    }

    trans(id: string, parameters?: any): string | any[] {
        const translation = this.translations[id];

        if (translation) {
            return this.apply(translation, parameters || {});
        }

        return id;
    }

    transText(id: string, parameters?: any): string {
        return extractText(this.trans(id, parameters));
    }

    transChoice(id: string, number: number, parameters: any): string | any[] {
        let translation: string = this.translations[id];

        if (translation) {
            translation = this.pluralize(translation, number);

            return this.apply(translation, parameters || {});
        }

        return id;
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
                open[0].push({ tag: 'span', text: part });
            }
        });

        return hydrated.filter(part => part);
    }

    pluralize(translation: string, number: number): string | undefined {
        const sPluralRegex = new RegExp(/^\w+\: +(.+)$/),
            cPluralRegex = new RegExp(
                /^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/
            ),
            iPluralRegex = new RegExp(/^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/),
            standardRules = [],
            explicitRules = [];

        translation.split('|').forEach(part => {
            if (cPluralRegex.test(part)) {
                const matches = part.match(cPluralRegex);
                explicitRules[matches[0]] = matches[matches.length - 1];
            } else if (sPluralRegex.test(part)) {
                const matches = part.match(sPluralRegex);
                standardRules.push(matches[1]);
            } else {
                standardRules.push(part);
            }
        });

        explicitRules.forEach((rule, e) => {
            if (iPluralRegex.test(e)) {
                const matches = e.match(iPluralRegex);

                if (matches[1]) {
                    const ns = matches[2].split(',');

                    for (let n in ns) {
                        if (number == ns[n]) {
                            return explicitRules[e];
                        }
                    }
                } else {
                    const leftNumber = this.convertNumber(matches[4]);
                    const rightNumber = this.convertNumber(matches[5]);

                    if (
                        ('[' === matches[3] ? number >= leftNumber : number > leftNumber) &&
                        (']' === matches[6] ? number <= rightNumber : number < rightNumber)
                    ) {
                        return explicitRules[e];
                    }
                }
            }
        });

        return standardRules[this.pluralPosition(number, this.locale)] || standardRules[0] || undefined;
    }

    convertNumber(number: string): number {
        if ('-Inf' === number) {
            return Number.NEGATIVE_INFINITY;
        } else if ('+Inf' === number || 'Inf' === number) {
            return Number.POSITIVE_INFINITY;
        }

        return parseInt(number, 10);
    }

    pluralPosition(number: number, locale: string): number {
        if ('pt_BR' === locale) {
            locale = 'xbr';
        }

        if (locale.length > 3) {
            locale = locale.split('_')[0];
        }

        switch (locale) {
            case 'bo':
            case 'dz':
            case 'id':
            case 'ja':
            case 'jv':
            case 'ka':
            case 'km':
            case 'kn':
            case 'ko':
            case 'ms':
            case 'th':
            case 'vi':
            case 'zh':
                return 0;

            case 'af':
            case 'az':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'tr':
            case 'ur':
            case 'zu':
                return number == 1 ? 0 : 1;

            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'xbr':
            case 'ti':
            case 'wa':
                return number === 0 || number == 1 ? 0 : 1;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                return number % 10 == 1 && number % 100 != 11
                    ? 0
                    : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20)
                    ? 1
                    : 2;

            case 'cs':
            case 'sk':
                return number == 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;

            case 'ga':
                return number == 1 ? 0 : number == 2 ? 1 : 2;

            case 'lt':
                return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;

            case 'sl':
                return number % 100 == 1 ? 0 : number % 100 == 2 ? 1 : number % 100 == 3 || number % 100 == 4 ? 2 : 3;

            case 'mk':
                return number % 10 == 1 ? 0 : 1;

            case 'mt':
                return number == 1 ? 0 : number === 0 || (number % 100 > 1 && number % 100 < 11) ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;

            case 'lv':
                return number === 0 ? 0 : number % 10 == 1 && number % 100 != 11 ? 1 : 2;

            case 'pl':
                return number == 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;

            case 'cy':
                return number == 1 ? 0 : number == 2 ? 1 : number == 8 || number == 11 ? 2 : 3;

            case 'ro':
                return number == 1 ? 0 : number === 0 || (number % 100 > 0 && number % 100 < 20) ? 1 : 2;

            case 'ar':
                return number === 0 ? 0 : number == 1 ? 1 : number == 2 ? 2 : number >= 3 && number <= 10 ? 3 : number >= 11 && number <= 99 ? 4 : 5;

            default:
                return 0;
        }
    }
}
