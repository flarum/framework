/// <reference path="../@types/translator-icu-rich.d.ts" />
import { RichMessageFormatter } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
type Translations = Record<string, string>;
type TranslatorParameters = Record<string, unknown>;
export default class Translator {
    /**
     * A map of translation keys to their translated values.
     */
    translations: Translations;
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
    trans(id: string, parameters?: TranslatorParameters): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
}
export {};
