import abbreviateNumber from '../../../../src/common/utils/abbreviateNumber';

test('does not change small numbers', () => {
  expect(abbreviateNumber(1)).toBe('1');
});

test('abbreviates large numbers', () => {
  expect(abbreviateNumber(1000000)).toBe('1M');
  expect(abbreviateNumber(100500)).toBe('100.5K');
});

test('abbreviates large numbers with decimal places', () => {
  expect(abbreviateNumber(100500)).toBe('100.5K');
  expect(abbreviateNumber(13234)).toBe('13.2K');
});
