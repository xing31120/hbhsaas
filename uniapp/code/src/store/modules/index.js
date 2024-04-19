const files = require.context('.', false, /\.js$/)
import { basename } from 'path';

const modules = {}

files.keys().forEach(key => {
	if (key === './index.js') return false

	const name = basename(key, '.js');
	modules[name] = files(key).default;
})

export default modules;