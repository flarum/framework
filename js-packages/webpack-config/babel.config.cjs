module.exports = {
  assumptions: {
    // Defines assumptions Babel can make about our
    // code to better optimise it.
    //
    // These are rarely used features that are generally
    // considered very bad practice anyway.
    //
    // See: https://babeljs.io/docs/en/assumptions
    constantSuper: true,
    ignoreFunctionLength: true,
    noDocumentAll: true,
    noNewArrows: true,
    privateFieldsAsProperties: true,
  },
  targets: {
    // `not android > 0` means the build-in Android browser used up to Android 4.4 KitKat.
    browsers: '>0.2%, not dead, not android > 0, not operamini all',
  },
  presets: [
    require.resolve('@babel/preset-react'),
    require.resolve('@babel/preset-typescript'),
    [
      require.resolve('@babel/preset-env'),
      {
        modules: 'auto',
      },
    ],
  ],
  plugins: [
    [require.resolve('@babel/plugin-transform-runtime'), { useESModules: true }],
    [require.resolve('@babel/plugin-proposal-class-properties')],
    [require.resolve('@babel/plugin-proposal-private-methods')],
    [
      require.resolve('@babel/plugin-transform-react-jsx'),
      {
        pragma: 'm',
        pragmaFrag: "'['",
        useBuiltIns: true,
      },
    ],
  ],
};
