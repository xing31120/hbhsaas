//vue.config.js
const TransformPages = require('uni-read-pages')
const { webpack } = new TransformPages()
module.exports = {
	transpileDependencies:['uni-simple-router', 'luch-request'],
    configureWebpack: {
        plugins: [
            new webpack.DefinePlugin({
				ROUTES: webpack.DefinePlugin.runtimeValue(() => {
					const tfPages = new TransformPages({
						includes: ['path', 'name', 'meta']
					});
					return JSON.stringify(tfPages.routes)
				}, true )
			})
        ]
    }
}