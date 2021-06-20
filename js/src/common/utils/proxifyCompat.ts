export default (compat: { [key: string]: any }, namespace: string) => {
  // regex to replace common/ and NAMESPACE/ for core & core extensions
  // and remove .js, .ts and .tsx extensions
  // e.g. admin/utils/extract --> utils/extract
  // e.g. tags/common/utils/sortTags --> tags/utils/sortTags
  const regex = new RegExp(`^(?:\w+\/)?(?:${namespace}|common)\/(.+?)(?:\.(?:js|tsx?))?$`);

  return new Proxy(compat, {
    get: (obj, prop: string) => {
      if (obj[prop]) return obj[prop];

      const out = regex.exec(prop);

      return out && obj[out[1]];
    },
  });
};
