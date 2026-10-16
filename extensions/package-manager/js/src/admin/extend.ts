import Extend from 'flarum/common/extenders';
import ExtensionManagerPage from './components/ExtensionManagerPage';

/*
 * 🚨 Declarative, not `app.extensionData` at initializer time. Both work at
 * runtime, but the extender is the typed path — reaching for app.extensionData
 * means casting through `any` past a gap in Flarum 2's dist typings, and a cast
 * is where an upstream rename stops being a compile error and starts being a
 * page that silently never renders.
 */
export default [new Extend.Admin().page(ExtensionManagerPage)];
