import 'mithril-query/mithril-query';

declare global {
  namespace jest {
    interface Matchers<R> {
      toHaveElement(selector: any): R;
      toHaveElementAttr(selector: any, attribute: any, value: any): R;
      toHaveElementAttr(selector: any, attribute: any): R;
      toContainRaw(content: any): R;
    }
  }
}

export {};
