export default (compat: { [key: string]: any }, namespace: string) => {
  // regex to replace common/ and NAMESPACE/ for core & core extensions
  // e.g. admin/utils/extract --> utils/extract
  // e.g. tags/common/utils/sortTags --> tags/utils/sortTags
  const regex = new RegExp(`(\\w+\\/)?(${namespace}|common)\\/`);

  return new Proxy(compat, {
    get: (obj, prop: string) => obj[prop] || obj[prop.replace(regex, '$1')],
  });
};
