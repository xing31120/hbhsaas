export default {
	watch: {
		'list.loading'(val) {
			if (val) {
				uni.showLoading({
					title: '加载中...',
					mask: true
				})
			} else {
				uni.hideLoading()
			}
		}
	},
	data() {
		return {
			list: {
				data: [],
				page: 1,
				pageSize: 20,
				loading: true,
				finished: false,
				total: 0,
				totalPage: 0
			}
		}
	},
	methods: {
		resetList() {
			this.list = {
				data: [],
				page: 1,
				pageSize: 20,
				loading: true,
				finished: false,
				total: null,
				totalPage: null
			}
		},
		completes(res, listKey) {
			this.list.loading = false
			if (res.code !== 1) {
				this.list.finished = true
			}
			let listArr = res.data.list || res.data.data || res.data[listKey]
			this.list.data = this.list.page === 1 ? listArr : this.list.data.concat(listArr)
			this.list.total = res.data.totalPage * this.list.pageSize
			this.list.totalPage = res.data.totalPage || res.totalPage
			// if(res.data.moreData) {
			// 	this.list.moreData = res.data.moreData
			// }
			if (!res.data.moreData) {
				this.list.finished = true
			} else {
				++this.list.page
			}
		}
	}

}
