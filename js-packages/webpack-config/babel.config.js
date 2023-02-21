module.exports = {
  presets: [
    require.resolve('@babel/preset-react'),
    require.resolve('@babel/preset-typescript'),
    [
      require.resolve('@babel/preset-env'),
      {
        modules: false,
        loose: true,
      },
    ],
  ],
  plugins: [
    [require.resolve('@babel/plugin-transform-runtime'), { useESModules: true }],
    [require.resolve('@babel/plugin-proposal-class-properties'), { loose: true }],
    [require.resolve('@babel/plugin-proposal-private-methods'), { loose: true }],
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
