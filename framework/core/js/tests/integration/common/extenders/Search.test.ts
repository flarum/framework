import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Search from '../../../../src/common/extenders/Search';
import { KeyValueGambit } from '../../../../src/common/query/IGambit';
import { app } from '../../../../src/forum';

beforeAll(() => bootstrapForum());

describe('Search extender', () => {
  it('gambit does not work before registering it', () => {
    app.boot();

    expect(app.search.gambits.apply('discussions', { q: 'lorem keanu:reeves' })).toStrictEqual({
      q: 'lorem keanu:reeves',
    });
  });

  it('works after registering it', () => {
    app.bootExtensions({
      test: {
        extend: [new Search().gambit('discussions', KeanuGambit)],
      },
    });

    app.boot();

    expect(app.search.gambits.apply('discussions', { q: 'lorem keanu:reeves' })).toStrictEqual({
      q: 'lorem',
      keanu: 'reeves',
    });
  });
});

class KeanuGambit extends KeyValueGambit {
  filterKey(): string {
    return 'keanu';
  }

  hint(): string {
    return 'Keanu is breathtaking';
  }

  key(): string {
    return 'keanu';
  }
}
