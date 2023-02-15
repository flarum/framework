import Translator from '../../../../src/common/Translator';
import extractText from "../../../../src/common/utils/extractText";

/*
 * These tests should be in sync with PHP tests in `tests/unit/Locale/TranslatorTest.php`, to make sure that JS
 * translator works in the same way as JS translator.
 */

test('placeholders encoding', () => {
	const translator = new Translator();
	translator.setLocale('en');
	translator.addTranslations({
		'test1': "test1 {placeholder} test1",
		'test2': "test2 {placeholder} test2",
	});

	expect(extractText(translator.trans('test1', {'{placeholder}': "'"}))).toBe("test1 ' test1");
	expect(extractText(translator.trans('test1', {'{placeholder}': translator.trans('test2', {'{placeholder}': "'"})}))).toBe("test1 test2 ' test2 test1");
});

test('missing placeholders', () => {
	const translator = new Translator();
	translator.setLocale('en');
	translator.addTranslations({
		'test1': "test1 {placeholder} test1",
	});

	expect(extractText(translator.trans('test1', {}))).toBe('test1 {placeholder} test1');
});

test('escaped placeholders', () => {
	const translator = new Translator();
	translator.setLocale('en');
	translator.addTranslations({
		'test3': "test1 {placeholder} '{placeholder}' test1",
	});

	expect(extractText(translator.trans('test3', {placeholder: "'"}))).toBe("test1 ' {placeholder} test1");
});
