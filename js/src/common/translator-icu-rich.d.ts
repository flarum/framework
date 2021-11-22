declare module '@askvortsov/rich-icu-message-formatter' {
  type IValues = Record<string, any>;

  type ITypeHandler = (
    value: string,
    matches: string,
    locale: string,
    values: IValues,
    format: (message: string, values: IValues) => string
  ) => string;
  type IRichHandler = (tag: any, values: IValues, contents: string) => any;

  type ValueOrArray<T> = T | ValueOrArray<T>[];
  type NestedStringArray = ValueOrArray<string>;

  export class RichMessageFormatter {
    locale: string | null;
    constructor(locale: string | null, typeHandlers: Record<string, ITypeHandler>, richHandler: IRichHandler);

    format(message: string, values: IValues): string;
    process(message: string, values: IValues): NestedStringArray;
    rich(message: string, values: IValues): NestedStringArray;
  }

  export function mithrilRichHandler(tag: any, values: IValues, contents: string): any;
}
