declare module '@askvortsov/rich-icu-message-formatter' {
  import Mithril from 'mithril';

  type IValues = Record<string, any>;

  type ITypeHandler = (
    value: string,
    matches: string,
    locale: string,
    values: IValues,
    format: (message: string, values: IValues) => string
  ) => string;
  type IRichHandler = (tag: any, values: IValues, contents: string) => any;

  export class RichMessageFormatter {
    locale: string | null;
    constructor(locale: string | null, typeHandlers: Record<string, ITypeHandler>, richHandler: IRichHandler);

    format(message: string, values: IValues): string;
    process(message: string, values: IValues): Mithril.Children;
    rich(message: string, values: IValues): Mithril.Children;
  }

  export function mithrilRichHandler(tag: any, values: IValues, contents: string): any;
}
