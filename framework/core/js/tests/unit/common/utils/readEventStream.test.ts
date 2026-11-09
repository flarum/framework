import { streamBuffering, type StreamArrival } from '../../../../src/common/utils/readEventStream';

/**
 * Events the server sent at the given milliseconds, arriving at the given times.
 */
function arrivals(times: [ms: number, at: number][]): StreamArrival[] {
  return times.map(([ms, at]) => ({ event: { type: 'tick', ms }, at }));
}

describe('streamBuffering', () => {
  it('says the events streamed when they arrive as far apart as they were sent', () => {
    expect(
      streamBuffering(
        arrivals([
          [0, 5000],
          [250, 5260],
          [500, 5505],
          [1000, 6012],
        ])
      )
    ).toBe('streamed');
  });

  it('says they were held back when they arrive together', () => {
    expect(
      streamBuffering(
        arrivals([
          [0, 6000],
          [250, 6000],
          [500, 6000],
          [1000, 6001],
        ])
      )
    ).toBe('buffered');
  });

  it("can't tell when the server sent them too close together", () => {
    expect(
      streamBuffering(
        arrivals([
          [0, 100],
          [40, 100],
        ])
      )
    ).toBe('unknown');
    expect(streamBuffering(arrivals([[0, 100]]))).toBe('unknown');
  });

  it('allows for network delay that bunches some events', () => {
    // A slow first chunk, then the rest as they came: still streamed.
    expect(
      streamBuffering(
        arrivals([
          [0, 5400],
          [250, 5400],
          [500, 5510],
          [1000, 6010],
        ])
      )
    ).toBe('streamed');
  });
});
