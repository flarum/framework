/**
 * Truncate a string to the given length, appending ellipses if necessary.
 */
export declare function truncate(string: string, length: number, start?: number): string;
/**
 * Create a slug out of the given string. Non-alphanumeric characters are
 * converted to hyphens.
 *
 * NOTE: This method does not use the comparably sophisticated transliteration
 * mechanism that is employed in the backend. Therefore, it should only be used
 * to *suggest* slugs that can be overridden by the user.
 */
export declare function slug(string: string): string;
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
