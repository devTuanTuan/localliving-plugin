const path = require('path');
const CopyPlugin = require('copy-webpack-plugin');

let outputDir = path.resolve(__dirname, './release/localliving-plugin');

require('dotenv').config();
if (process.env.NODE_ENV === 'development') {
  outputDir =
    process.env.wwwDir + '/' + process.env.projectname + '/wp-content/plugins/localliving-plugin/';
}
module.exports = (env) => {
  console.log(outputDir);
  return {
    entry: './src/entry/index.js',
    output: {
      filename: '[name].js',
      path: outputDir + '/dist',
    },
    plugins: [
      new CopyPlugin({
        patterns: [
          {
            from: path.resolve(__dirname, './plugins/localliving-plugin'),
            to: outputDir,
          },
          {
            from: path.resolve(__dirname, './vendor'),
            to: outputDir + '/vendor',
          },
          {
            from: path.resolve(__dirname, './node_modules/bootstrap-datepicker'),
            to: outputDir + '/assets/bootstrap-datepicker',
          },
          {
            from: path.resolve(__dirname, './node_modules/bootstrap'),
            to: outputDir + '/assets/bootstrap',
          },
          {
            from: path.resolve(__dirname, './node_modules/bootstrap-toggle'),
            to: outputDir + '/assets/bootstrap-toggle',
          },
          {
            from: path.resolve(__dirname, './node_modules/moment'),
            to: outputDir + '/assets/moment',
          },
          {
            from: path.resolve(__dirname, './node_modules/bs5-lightbox'),
            to: outputDir + '/assets/bs5-lightbox',
          },
          {
            from: path.resolve(__dirname, './node_modules/puppeteer'),
            to: outputDir + '/assets/puppeteer',
          },
          {
            from: path.resolve(__dirname, './node_modules/puppeteer-core'),
            to: outputDir + '/assets/puppeteer-core',
          },
          {
            from: path.resolve(__dirname, './assets/js/auto_upload_puppeteer.js'),
            to: outputDir + '/assets/auto_upload_puppeteer.js',
          },
        ],
      }),
    ],
    module: {
      rules: [
        {
          test: /\.(sa|sc|c)ss$/,
          use: ['style-loader', 'css-loader', 'sass-loader'],
        },
      ],
    },
  };
};
