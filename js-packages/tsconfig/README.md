# Flarum Typescript Config

A standardised `tsconfig.json` for use in Typescript Flarum extensions.

## Usage

You'll need to configure a `tsconfig.json` file to ensure your IDE sets up Typescript support correctly.

You need to install this package as a dev dependency:

```properties
npm install --save-dev flarum-tsconfig
yarn add --dev flarum-tsconfig
```

A baseline `tsconfig.json` is provided below that you can modify as needed. This file needs to be copied into your extension's `js` folder for your IDE to apply the correct settings.

```jsonc
{
  // Use Flarum's tsconfig as a starting point
  "extends": "flarum-tsconfig",
  // This will match all .ts, .tsx, .d.ts, .js, .jsx files
  "include": ["src/**/*"],
  "compilerOptions": {
    // This will output typings to `dist-typings`
    "declarationDir": "./dist-typings"
  }
}
```
