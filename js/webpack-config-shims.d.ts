import * as Mithril from 'mithril';
import Stream from 'mithril/stream';

import * as _dayjs from 'dayjs';
import * as _$ from 'jquery';

interface m extends Mithril.Static {
    prop: typeof Stream;
}

declare global {
    const $: typeof _$;
    const m: m;
    const dayjs: typeof _dayjs;
}

export as namespace Mithril;

export {};
