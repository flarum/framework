import { readFile } from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';
import test from 'node:test';
import less from 'less';
import { chromium } from 'playwright-core';

const root = process.env.LAYOUT_CHECKOUT || process.cwd();
const styles = path.join(root, 'framework/core/less');
const source = await readFile(path.join(styles, 'common/variables.less'), 'utf8');
const { css } = await less.render(`${source}\n@import "common/Form";\n@import "admin/SettingsSection";\n@import "admin/AdminPage";`, {
  paths: [styles],
});

// An isolated rendering fixture: no forum, credentials, database, or server.
const section = (wide = false) => `<section class="SettingsSection" ${wide ? 'style="--settings-section-body-max-width: 1000px"' : ''}>
<header class="SettingsSection-header"><h3 class="SettingsSection-title">Colours</h3><p class="SettingsSection-description helpText">Choose colours for your forum. ${'Section help. '.repeat(
  30
)}</p></header>
<div class="SettingsSection-body"><label>Colour scheme<input value="Default"></label><div style="height:1100px">Settings</div><button class="section-save">Save section</button></div></section>`;

test('settings read vertically and share a save alignment at desktop and mobile widths', async () => {
  const browser = await chromium.launch({ executablePath: process.env.CHROME_BIN, args: ['--no-sandbox'] });
  try {
    const page = await browser.newPage();
    for (const host of ['AdminPage', 'ExtensionPage']) {
      for (const width of [1440, 1024, 390]) {
        await page.setViewportSize({ width, height: 800 });
        await page.setContent(`<style>${css}\nbody {margin:0; --body-bg:white; --control-bg:#eee;} .fixture {margin:40px 24px;} button {height:36px;} label,input {display:block;}</style>
<div class="${host} fixture"><form class="Form AdminPage-settingsForm--sectioned"><div class="Form-body">${section()}${section(
          true
        )}<div class="Form-group Form-controls"><button class="page-save">Save changes</button></div></div></form></div>`);
        const geometry = await page.evaluate(() => {
          const box = (selector) => {
            const r = document.querySelector(selector).getBoundingClientRect();
            return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width };
          };
          return {
            header: box('.SettingsSection-header'),
            body: box('.SettingsSection-body'),
            help: box('.SettingsSection-description'),
            section: box('.SettingsSection'),
            wide: box('.SettingsSection:last-of-type .SettingsSection-body'),
            sectionSave: box('.section-save'),
            pageSave: box('.page-save'),
            bar: box('.Form-controls'),
          };
        });
        const context = `${host} at ${width}px`;
        assert.ok(geometry.header.bottom <= geometry.body.top, `${context}: heading/help must sit above fields`);
        assert.equal(geometry.body.left, geometry.header.left, `${context}: heading and fields align`);
        assert.equal(geometry.sectionSave.left, geometry.pageSave.left, `${context}: save buttons align`);
        assert.equal(geometry.bar.width, geometry.section.width, `${context}: save bar spans content`);
        assert.ok(geometry.help.width <= 640, `${context}: help stays readable`);
        assert.ok(geometry.body.width <= 640, `${context}: standard fields stay readable`);
        assert.equal(geometry.wide.width, Math.min(1000, width - 48), `${context}: wide controls can use extra space`);
        await page.evaluate(() => window.scrollTo(0, 500));
        const afterScroll = await page
          .locator('.SettingsSection-header')
          .first()
          .evaluate((el) => ({ top: el.getBoundingClientRect().top, position: getComputedStyle(el).position }));
        assert.equal(afterScroll.position, 'static', `${context}: section help scrolls with its fields`);
        const save = await page.locator('.page-save').boundingBox();
        assert.ok(save.y >= 0 && save.y + save.height <= 800, `${context}: save remains reachable when scrolling`);
        console.log(`PASS ${context}: stacking, alignment, widths, scrolling`);
      }
    }
  } finally {
    await browser.close();
  }
});
