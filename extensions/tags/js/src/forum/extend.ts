import app from 'flarum/forum/app';
import TagsPage from "./components/TagsPage";
import Extend from "flarum/common/extenders";
import IndexPage from "flarum/forum/components/IndexPage";

export default [
  new Extend.Routes()
    .add('tags', '/tags', TagsPage)
    .add('tag', '/t/:tags', IndexPage)
    .helper('tag', (tag) => app.route('tag', { tags: tag.slug() })),
];
