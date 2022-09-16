import Extend from "flarum/common/extenders";
import MentionsUserPage from "./components/MentionsUserPage";

export default [
  new Extend.Routes()
    .add('user.mentions', '/u/:username/mentions', MentionsUserPage),
];
