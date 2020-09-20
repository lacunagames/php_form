const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: './src_frontend/index.js',
  devtool: 'source-map',
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: ['babel-loader'],
      },
      {
        test: /\.s(a|c)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'sass-loader',
          },
        ],
      },
      {
        test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: '',
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      path: __dirname + '/public/assets',
      publicPath: '',
      filename: 'bundle.css',
    }),
  ],
  resolve: {
    extensions: ['.js', '.scss'],
  },
  output: {
    path: __dirname + '/public/assets',
    publicPath: '',
    filename: 'bundle.js',
  },
};
