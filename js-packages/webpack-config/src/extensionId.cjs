module.exports = (name) => name === 'flarum/core'
  ? 'core'
  : name.replace('/flarum-ext-', '-').replace('/flarum-', '').replace('/', '-')
