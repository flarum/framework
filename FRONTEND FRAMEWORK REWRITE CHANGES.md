### Changes

* Mithril
    - See changes from v0.2.x @ https://mithril.js.org/migration-v02x.html
    - Kept `m.prop` and `m.withAttr`
    - Actual Promises are used now instead of `m.deferred`
* Component
    - Use new Mithril lifecycle hooks (`component.config` is gone)
    - `component.render` is gone
* Application
    - New different methods
    - `app.bus` for some event hooking
* Translator
    - Added `app.translator.transText`, automatically extracts text from `translator.trans` output

#### Forum
* Forum Application
    - Renamed to `Forum`
    - `app.search` is no longer global, extend using `extend`
