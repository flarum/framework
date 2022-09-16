import Extend from "flarum/common/extenders";
import FlagsPage from "./components/FlagsPage";

export default [
  new Extend.Routes()
    .add('flags', '/flags', FlagsPage),
];
