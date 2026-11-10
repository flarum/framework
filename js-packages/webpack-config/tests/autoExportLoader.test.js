/**
 * @jest-environment node
 */
import compiler from './compiler.js';

const compile = async (path, useFinalOutput = false) => {
  const stats = await compiler(path);
  return useFinalOutput
    ? stats.finalOutput
    : stats.toJson({
        source: true,
      }).modules[0].source;
};

test('A directory with index.js that exports multiple modules adds the directory as a module', async () => {
  let output = await compile('src/common/bars/index.js', true);
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/bars', bars)");

  output = await compile('src/common/bars/Acme.js');
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/bars/Acme', Acme)");

  output = await compile('src/common/bars/Foo.js');
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/bars/Foo', Foo)");
});

test('Simple default exports are added', async () => {
  const output = await compile('src/common/Test.js');
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/Test', Test)");
});

test('Named exports are added', async () => {
  const output = await compile('src/common/foos/namedExports.js');
  expect(output).toContain(
    "flarum.reg.add('flarum-framework', 'common/foos/namedExports', { baz: baz,foo: foo,Bar: Bar,sasha: sasha,flarum: flarum,david: david, })"
  );
});

test('Export as default from another module is added', async () => {
  const output = await compile('src/common/foos/exportDefaultFrom.js', true);
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/foos/exportDefaultFrom', potato");
});

test('Export from other modules is added', async () => {
  const output = await compile('src/common/foos/exportFrom.js', true);
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/foos/exportFrom', { potato: potato,franz: franz, }");
});

test('Export from with other named exports works', async () => {
  const output = await compile('src/common/foos/exportFromWithNamedExports.js', true);
  expect(output).toContain(
    "flarum.reg.add('flarum-framework', 'common/foos/exportFromWithNamedExports', { potato: potato,franz: franz,baz: baz,foo: foo,Bar: Bar,sasha: sasha,forum: forum,david: david, }"
  );
});

test('Anonymous async arrow functions do not cause invalid exports', async () => {
  const output = await compile('src/common/foos/asyncAnonymous.js');
  expect(output).not.toContain("flarum.reg.add('flarum-framework', 'common/foos/asyncAnonymous', async)");
});

test('Named async functions are exported correctly', async () => {
  const output = await compile('src/common/foos/asyncNamed.js');
  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/foos/asyncNamed', myAsyncFunc)");
});

/**
 * Builds a fixture and runs it, returning what it adds to the registry.
 * Fixtures are written as Babel leaves them, which this loader sees.
 */
const registered = async (path) => {
  const output = await compile(path, true);
  const added = [];

  new Function('flarum', output)({
    reg: { add: (namespace, id, value) => added.push({ id, value }) },
  });

  return added;
};

test('A default exported arrow function with one parameter is registered as the function', async () => {
  // `export default (x: number) => x + 1` comes out of Babel without the parentheses.
  const [entry, ...rest] = await registered('src/common/defaults/oneParamArrow.js');

  expect(rest).toEqual([]);
  expect(entry.id).toBe('common/defaults/oneParamArrow');
  expect(entry.value(1)).toBe(2);
});

test('A default exported arrow function with several parameters is registered', async () => {
  const [entry, ...rest] = await registered('src/common/defaults/twoParamArrow.js');

  expect(rest).toEqual([]);
  expect(entry.id).toBe('common/defaults/twoParamArrow');
  expect(entry.value(1, 2)).toBe(3);
});

test('A default exported call is registered as what it returns', async () => {
  expect(await registered('src/common/defaults/call.js')).toEqual([{ id: 'common/defaults/call', value: { made: true } }]);
});

test('A default exported property is registered as its value', async () => {
  expect(await registered('src/common/defaults/member.js')).toEqual([{ id: 'common/defaults/member', value: 'the bar' }]);
});

test('A default exported identifier is registered as it was', async () => {
  const output = await compile('src/common/defaults/identifier.js');

  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/defaults/identifier', Thing)");
  expect(await registered('src/common/defaults/identifier.js')).toEqual([{ id: 'common/defaults/identifier', value: { thing: true } }]);
});

test('A default exported named function is registered as it was', async () => {
  const output = await compile('src/common/defaults/namedFunction.js');

  expect(output).toContain("flarum.reg.add('flarum-framework', 'common/defaults/namedFunction', named)");
  expect((await registered('src/common/defaults/namedFunction.js'))[0].value()).toBe('named');
});

test('A default exported anonymous function declaration is left as it was', async () => {
  // Turning it into an expression to name it would lose its hoisting, which
  // circular imports can rely on.
  const output = await compile('src/common/defaults/anonymousFunction.js');

  expect(output).toContain('export default function () {');
  expect(await registered('src/common/defaults/anonymousFunction.js')).toEqual([]);
});

test('A default exported anonymous class that extends another is left as it was', async () => {
  const output = await compile('src/common/defaults/anonymousClassExtends.js');

  expect(output).not.toContain(', extends)');
  expect(await registered('src/common/defaults/anonymousClassExtends.js')).toEqual([]);
});
