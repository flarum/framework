import 'mithril-query/mithril-query';

declare global {
  namespace jest {
    interface Matchers<R> {
      toHaveElement(selector: any): R;
      toContainRaw(content: any): R;
    }
  }
}

export {};
