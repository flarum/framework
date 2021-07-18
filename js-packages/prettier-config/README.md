# Flarum Prettier Config

Flarum's official configuration for the Prettier code formatter.

For more info about shared Prettier configurations, [check out the Prettier documentation](https://prettier.io/docs/en/configuration.html#sharing-configurations).

## Usage

Add the `prettier` key to your `package.json`:

```jsonc
// package.json
{
  "name": "my-cool-package",
  "version": "1.0.0",
  "prettier": "@flarum/prettier-config"
  // ...
}
```

## Extending

You can extend our config with your own custom options, too. Import our config, then spread its require in a `.prettierrc.js` file.

Make sure your `package.json` doesn't have the `prettier` key, and that it has `@flarum/prettier-config` as a dev dependency.

```
npm i -D @flarum/prettier-config
yarn add -D @flarum/prettier-config
```

```js
// .prettierrc.js
module.exports = {
  ...require("@flarum/prettier-config"),
  semi: false,
};
```
