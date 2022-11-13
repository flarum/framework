import { expect } from '@jest/globals';

// Extend Jest with mithril component test matchers.
expect.extend({
  toHaveElement: intoMatcher((out: any, expected: any) => out.should.have(expected), 'Expected $received to have node $expected'),
  toContainRaw: intoMatcher((out: any, expected: any) => out.should.contain(expected), 'Expected $received to contain $expected'),
});

function intoMatcher(callback: Function, message: string) {
  return function (this: any, received: any, ...expected: any) {
    try {
      callback(received, ...expected);
      return { pass: true, message: () => '' };
    } catch (e) {
      return {
        pass: false,
        message: () => message.replace('$expected', this.utils.printExpected(expected)).replace('$received', this.utils.printReceived('component')),
      };
    }
  };
}
