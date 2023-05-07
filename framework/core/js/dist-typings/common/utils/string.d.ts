/**
 * Truncate a string to the given length, appending ellipses if necessary.
 */
export declare function truncate(string: string, length: number, start?: number): string;
/**
 * Create a slug out of the given string depending on the selected mode.
 * Invalid characters are converted to hyphens.
 *
 * NOTE: This method does not use the comparably sophisticated transliteration
 * mechanism that is employed in the backend. Therefore, it should only be used
 * to *suggest* slugs that can be overridden by the user.
 */
export declare function slug(string: string, mode?: SluggingMode): string;
declare enum SluggingMode {
    ALPHANUMERIC = "alphanum",
    UTF8 = "utf8"
}
/**
 * Strip HTML tags and quotes out of the given string, replacing them with
 * meaningful punctuation.
 */
export declare function getPlainContent(string: string): string;
export declare namespace getPlainContent {
    var removeSelectors: string[];
}
/**
 * Make a string's first character uppercase.
 */
export declare function ucfirst(string: string): string;
/**
 * Transform a camel case string to snake case.
 */
export declare function camelCaseToSnakeCase(str: string): string;
/**
 * Generate a random string (a-z, 0-9) of a given length.
 *
 * Providing a length of less than 0 will result in an error.
 *
 * @param length Length of the random string to generate
 * @returns A random string of provided length
 */
export declare function generateRandomString(length: number): string;
export {};
