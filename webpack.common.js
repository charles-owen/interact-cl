const path = require('path');

module.exports = {
	entry: {
		Interact: {
			import: path.resolve(__dirname, 'index.js'),
			dependOn: ['Course', 'Users', 'Site']
		}
	}
}
