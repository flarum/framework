import type { Dayjs } from 'dayjs';
import formatMessage, { Translation } from 'format-message';
import ItemList from './utils/ItemList';
type Translations = {
    [key: string]: string | Translation;
};
type TranslatorParameters = Record<string, unknown>;
type DateTimeFormatCallback = (id?: string) => string | void;
export default class Translator {
    /**
     * A map of translation keys to their translated values.
     */
    get translations(): Translations;
    /**
     * A item list of date time format callbacks.
     */
    dateTimeFormats: ItemList<DateTimeFormatCallback>;
    /**
     * The underlying ICU MessageFormatter util.
     */
    protected formatter: typeof formatMessage;
    /**
     * Sets the formatter's locale to the provided value.
     */
    setLocale(locale: string): void;
    /**
     * Returns the formatter's current locale.
     */
    getLocale(): string;
    addTranslations(translations: Translations): void;
    /**
     * A temporary system to preprocess parameters.
     * Should not be used by extensions.
     *
     * @internal
     */
    protected preprocessParameters(parameters: TranslatorParameters, translation: string | Translation): TranslatorParameters;
    trans(id: string, parameters: TranslatorParameters): any[];
    trans(id: string, parameters: TranslatorParameters, extract: false): any[];
    trans(id: string, parameters: TranslatorParameters, extract: true): string;
    trans(id: string): any[] | string;
    /**
     * Formats the time.
     *
     * The format of the time will be chosen by the following order:
     * - Custom format defined in the item list.
     * - The format defined in current locale.
     * - DayJS default format.
     */
    formatDateTime(time: Dayjs, id: string): string;
    /**
     * Backwards compatibility for translations such as `<a href='{href}'>`, the old
     * formatter supported that, but the new one doesn't, so attributes are auto dropped
     * to avoid errors.
     *
     * @private
     */
    private preprocessTranslation;
}
export {};
