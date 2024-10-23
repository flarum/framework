import type { Dayjs } from 'dayjs';
import { RichMessageFormatter, NestedStringArray } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import ItemList from './utils/ItemList';
type Translations = Record<string, string>;
type TranslatorParameters = Record<string, unknown>;
type DateTimeFormatCallback = (id?: string) => string | void;
export default class Translator {
    /**
     * A map of translation keys to their translated values.
     */
    translations: Translations;
    /**
     * A item list of date time format callbacks.
     */
    dateTimeFormats: ItemList<DateTimeFormatCallback>;
    /**
     * The underlying ICU MessageFormatter util.
     */
    protected formatter: RichMessageFormatter;
    /**
     * Sets the formatter's locale to the provided value.
     */
    setLocale(locale: string): void;
    /**
     * Returns the formatter's current locale.
     */
    getLocale(): string | null;
    addTranslations(translations: Translations): void;
    /**
     * An extensible entrypoint for extenders to register type handlers for translations.
     */
    protected formatterTypeHandlers(): {
        plural: typeof pluralTypeHandler;
        select: typeof selectTypeHandler;
    };
    /**
     * A temporary system to preprocess parameters.
     * Should not be used by extensions.
     * TODO: An extender will be added in v1.x.
     *
     * @internal
     */
    protected preprocessParameters(parameters: TranslatorParameters): TranslatorParameters;
    trans(id: string, parameters: TranslatorParameters): NestedStringArray;
    trans(id: string, parameters: TranslatorParameters, extract: false): NestedStringArray;
    trans(id: string, parameters: TranslatorParameters, extract: true): string;
    trans(id: string): NestedStringArray | string;
    /**
     * Formats the time.
     *
     * The format of the time will be chosen by the following order:
     * - Custom format defined in the item list.
     * - The format defined in current locale.
     * - DayJS default format.
     */
    formatDateTime(time: Dayjs, id: string): string;
}
export {};
