export default {
	catalogList: {
		type: 'get',
		url: '/api/v4/catalog/list',
		catchName: {
			value: 'catalog',
			position: 'after'
		}
	}
}
