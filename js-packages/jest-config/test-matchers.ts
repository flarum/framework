import { expect } from '@jest/globals';

// Extend Jest with mithril component test matchers.
expect.extend({
  toHaveElement: intoMatcher((out: any, expected: any) => out.should.have(expected), 'Expected $received to have node $expected'),
  toContainRaw: intoMatcher((out: any, expected: any) => out.should.contain(expected), 'Expected $received to contain $expected'),
  toHaveElementAttr: intoMatcher(function (out: any, selector: string, attribute: string, value: string | undefined) {
    out.should.have(selector);

    const node = out.find(selector)[0];

    const attr = node[attribute] ?? node._attrsByQName[attribute]?.data ?? undefined;

    const onlyTwoArgs = value === undefined;

    if (!node || (!onlyTwoArgs && attr !== value) || (onlyTwoArgs && !attr)) {
      throw new Error(`Expected ${selector} to have attribute ${attribute} with value ${value}, but found ${node[attribute]}`);
    }
  }, 'Expected $received to have attribute $expected with value $value'),
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
