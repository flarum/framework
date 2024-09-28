import Forum from './ForumApplication';

const app = new Forum();

// @ts-expect-error We need to do this for backwards compatibility purposes.
window.app = app;

export default app;
