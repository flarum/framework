import { SequentialBulkRunner } from '../../../../src/common/resource/BulkRunner';

describe('SequentialBulkRunner', () => {
  test('runs every op with at most 3 in flight and reports the failed one', async () => {
    const models = [1, 2, 3, 4, 5].map((id) => ({ id }));
    const failure = new Error('refused');
    const progress: [number, number][] = [];
    let inFlight = 0;
    let maxInFlight = 0;

    const result = await new SequentialBulkRunner().run(
      models,
      (model) => {
        inFlight++;
        maxInFlight = Math.max(maxInFlight, inFlight);

        return new Promise((resolve, reject) =>
          setTimeout(
            () => {
              inFlight--;

              if (model.id === 3) reject(failure);
              else resolve(undefined);
            },
            5 * (6 - model.id)
          )
        );
      },
      (done, total) => progress.push([done, total])
    );

    expect(result.ok).toHaveLength(4);
    expect(result.ok).toEqual([models[0], models[1], models[3], models[4]]);
    expect(result.failed).toHaveLength(1);
    expect(result.failed[0].model).toBe(models[2]);
    expect(result.failed[0].error).toBe(failure);
    expect(maxInFlight).toBe(3);
    expect(progress.at(-1)).toEqual([5, 5]);
  });
});
