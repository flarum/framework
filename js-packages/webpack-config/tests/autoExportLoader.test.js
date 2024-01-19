/**
 * @jest-environment node
 */
import compiler from './compiler.js';
import 'regenerator-runtime/runtime';

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
