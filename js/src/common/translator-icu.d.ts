declare module '@ultraq/icu-message-formatter' {
  export function pluralTypeHandler(
    value: string,
    matches: string,
    locale: string,
    values: Record<string, any>,
    format: (text: string, values: Record<string, any>) => string
  ): string;

  export function selectTypeHandler(
    value: string,
    matches: string,
    locale: string,
    values: Record<string, any>,
    format: (text: string, values: Record<string, any>) => string
  ): string;
}
