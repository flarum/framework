import fs from 'fs';
import path from 'path';

/**
 * `common/resource` is generic: it may import only other `common` modules and mithril, never
 * admin or forum code.
 */
describe('resource import boundary', () => {
  const root = path.join(__FLARUM_CORE_DIR__, 'src', 'common', 'resource');
  const common = path.join(__FLARUM_CORE_DIR__, 'src', 'common');

  function files(dir: string): string[] {
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
      const full = path.join(dir, entry.name);

      return entry.isDirectory() ? files(full) : /\.tsx?$/.test(entry.name) ? [full] : [];
    });
  }

  test('has source files', () => {
    expect(files(root).length).toBeGreaterThan(0);
  });

  test('imports only common modules and mithril', () => {
    const offending: string[] = [];

    for (const file of files(root)) {
      const source = fs.readFileSync(file, 'utf8');

      for (const match of source.matchAll(/(?:import|export)[^'"]*?from\s+['"]([^'"]+)['"]/g)) {
        const specifier = match[1];

        if (specifier === 'mithril') continue;

        const resolved = specifier.startsWith('.') ? path.resolve(path.dirname(file), specifier) : null;

        if (!resolved || !resolved.startsWith(common + path.sep)) {
          offending.push(`${path.relative(root, file)}: ${specifier}`);
        }
      }
    }

    expect(offending).toEqual([]);
  });

  test('uses only core.lib.* translation keys, never core.admin.* or core.forum.*', () => {
    const offending = files(root).flatMap((file) =>
      [...fs.readFileSync(file, 'utf8').matchAll(/['"`](core\.(?:admin|forum)\.[\w.]+)/g)].map((match) => `${path.relative(root, file)}: ${match[1]}`)
    );

    expect(offending).toEqual([]);
  });

  test('registers no resource type of its own as unsearchable', () => {
    const RelationInput = fs.readFileSync(path.join(root, 'inputs', 'RelationInput.tsx'), 'utf8');

    expect(RelationInput).toMatch(/static UNSEARCHABLE_TYPES = new Set<string>\(\);/);
  });
});
