import Translator from '../../../../src/common/Translator';
import extractText from '../../../../src/common/utils/extractText';

/*
 * These tests should be in sync with PHP tests in `tests/unit/Locale/TranslatorTest.php`, to make sure that JS
 * translator works in the same way as JS translator.
 */

test('placeholders encoding', () => {
  const translator = new Translator();
  translator.addTranslations({
    test1: 'test1 {placeholder} test1',
    test2: 'test2 {placeholder} test2',
  });

  expect(extractText(translator.trans('test1', { placeholder: "'" }))).toBe("test1 ' test1");
  expect(extractText(translator.trans('test1', { placeholder: translator.trans('test2', { placeholder: "'" }) }))).toBe("test1 test2 ' test2 test1");
});

// This is how the backend translator behaves. The only discrepancy with the frontend translator.
// test('missing placeholders', () => {
//   const translator = new Translator();
//   translator.addTranslations({
//     test1: 'test1 {placeholder} test1',
//   });
//
//   expect(extractText(translator.trans('test1', {}))).toBe('test1 {placeholder} test1');
// });

test('missing placeholders', () => {
  const translator = new Translator();
  translator.addTranslations({
    test1: 'test1 {placeholder} test1',
  });

  expect(extractText(translator.trans('test1', {}))).toBe('test1 {undefined} test1');
});

test('escaped placeholders', () => {
  const translator = new Translator();
  translator.addTranslations({
    test3: "test1 {placeholder} '{placeholder}' test1",
  });

  expect(extractText(translator.trans('test3', { placeholder: "'" }))).toBe("test1 ' {placeholder} test1");
});

test('plural rules', () => {
  const translator = new Translator();
  translator.addTranslations({
    test4: '{pageNumber, plural, =1 {{forumName}} other {Page # - {forumName}}}',
  });

  expect(extractText(translator.trans('test4', { forumName: 'A & B', pageNumber: 1 }))).toBe('A & B');
  expect(extractText(translator.trans('test4', { forumName: 'A & B', pageNumber: 2 }))).toBe('Page 2 - A & B');
});

test('plural rules 2', () => {
  const translator = new Translator();
  translator.setLocale('pl');
  translator.addTranslations({
    test5: '{count, plural, one {# post} few {# posty} many {# postów} other {# posta}}',
  });

  expect(extractText(translator.trans('test5', { count: 1 }))).toBe('1 post');
  expect(extractText(translator.trans('test5', { count: 2 }))).toBe('2 posty');
  expect(extractText(translator.trans('test5', { count: 5 }))).toBe('5 postów');
  expect(extractText(translator.trans('test5', { count: 1.5 }))).toBe('1,5 posta');
});
