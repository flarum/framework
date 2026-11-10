import type Stream from 'flarum/common/utils/Stream';

declare module 'flarum/common/Application' {
  export default interface Application {
    pageInfo: Stream<Record<string, any>>;
  }
}

declare global {
  interface Window {
    iFrameResizer: { readyCallback: () => void };
    parentIFrame: {
      getPageInfo: (callback: (info: Record<string, any>) => void) => void;
      scrollTo: (x: number, y: number) => void;
      scrollToOffset: (x: number, y: number) => void;
    };
  }
}

export {};
