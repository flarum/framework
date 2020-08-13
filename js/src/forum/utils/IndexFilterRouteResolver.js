import IndexPage from '../components/IndexPage';

export default {
  isIndexPage: true,

  onmatch: (params, path) => {
    const route = Object.values(app.routes).find((o) => o.path === path);

    if (route && route.component && !route.component.isIndexPage) {
      return route.component;
    }

    return IndexPage;
  },

  render: (vnode) => vnode,
};
