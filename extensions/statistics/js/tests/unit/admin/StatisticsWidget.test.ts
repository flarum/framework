import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import jsYaml from 'js-yaml';
import flatten from 'flat';

import StatisticsWidget from '../../../src/admin/components/StatisticsWidget';
import { Chart } from '../../stubs/frappe-charts';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../framework/core/js');
const localeFile = resolve(testDir, '../../../../locale/en.yml');

const DAY = 86400;

/** Midnight UTC today, the epoch the widget builds its periods around. */
const today = (() => {
  const date = new Date();
  date.setUTCHours(0, 0, 0, 0);
  return date.getTime() / 1000;
})();

/**
 * Counts keyed by day, as the API returns them. Each day carries a distinct
 * value so an assertion pins down *which* window was read, and in what order —
 * not merely that something non-zero came back.
 *
 * Days 1-7 ago (the "last 7 days" period) hold the day's own offset; days 8-14
 * ago (the period before it) hold ten times theirs.
 */
const counts: Record<number, number> = {};
for (let daysAgo = 1; daysAgo <= 7; daysAgo++) counts[today - daysAgo * DAY] = daysAgo;
for (let daysAgo = 8; daysAgo <= 14; daysAgo++) counts[today - daysAgo * DAY] = daysAgo * 10;

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapAdmin();
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(localeFile, 'utf8')) as object));
});

/**
 * A widget already holding loaded data, bypassing the XHRs that normally
 * populate it.
 */
function makeWidget(selectedPeriod: string): StatisticsWidget {
  const widget = new StatisticsWidget();

  widget.periods = {
    today: { start: today, end: today + DAY, step: 3600 },
    last_7_days: { start: today - DAY * 7, end: today, step: DAY },
    previous_7_days: { start: today - DAY * 14, end: today - DAY * 7, step: DAY },
    last_28_days: { start: today - DAY * 28, end: today, step: DAY },
    previous_28_days: { start: today - DAY * 28 * 2, end: today - DAY * 28, step: DAY },
    last_12_months: { start: today - DAY * 364, end: today, step: DAY * 7 },
  };

  widget.selectedEntity = 'users';
  widget.selectedPeriod = selectedPeriod;
  widget.loadingLifetime = false;
  widget.lifetimeData = { users: 100, discussions: 50, posts: 200 };

  for (const entity of widget.entities) {
    widget.timedData[entity] = { ...counts };
    widget.customPeriodData[entity] = { ...counts };
    widget.loadingTimed[entity] = 'loaded';
    widget.loadingCustom[entity] = 'loaded';
  }

  return widget;
}

/** The datasets the widget would have plotted, keyed by their translated name. */
function draw(widget: StatisticsWidget): Record<string, number[]> {
  Chart.lastData = null;

  widget.drawChart({ dom: document.createElement('div') } as any);

  expect(Chart.lastData).not.toBeNull();

  return Object.fromEntries(Chart.lastData!.datasets.map((dataset) => [dataset.name, dataset.values]));
}

describe('the chart the statistics widget plots', () => {
  it('plots the selected period, oldest bucket first', () => {
    const datasets = draw(makeWidget('last_7_days'));

    // Buckets run from 7 days ago up to yesterday; today itself is excluded,
    // since the period ends at midnight this morning.
    expect(datasets['Current period']).toEqual([7, 6, 5, 4, 3, 2, 1]);
  });

  it('plots the period before it alongside, bucket for bucket', () => {
    const datasets = draw(makeWidget('last_7_days'));

    // The same seven buckets shifted back by the period's own length, so
    // 14 days ago through 8 days ago — the x10 values.
    expect(datasets['Previous period']).toEqual([140, 130, 120, 110, 100, 90, 80]);
  });

  it('gives the previous period a bucket for every bucket of the current one', () => {
    const datasets = draw(makeWidget('last_7_days'));

    expect(datasets['Previous period']).toHaveLength(datasets['Current period'].length);
  });
});

describe('the per-entity count for a custom date range', () => {
  it('counts the whole selected range', () => {
    const widget = makeWidget('custom');

    // 14 days ago through 8 days ago — the same window as "previous 7 days".
    widget.customPeriod = { start: today - DAY * 14, end: today - DAY * 7 };

    const container = document.createElement('div');
    m.render(container, widget.content());

    // The element carries the unabbreviated count in its title attribute.
    const period = container.querySelector('.StatisticsWidget-period');

    expect(period?.getAttribute('title')).toBe(String(140 + 130 + 120 + 110 + 100 + 90 + 80));
  });
});
