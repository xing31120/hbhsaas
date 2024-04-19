import Vue from 'vue'
/** 基本用户信息 */
export default {
	namespaced: true,
	state: {
		pagePath: '',
		shopConfig: {}
	},
	mutations: {
		SET_PAGE_PATH(state, path) {
			state.pagePath = encodeURIComponent(path)
		},
		SET_SHOP_CONFIG(state, data) {
			state.shopConfig = data
		}
	},
	actions: {
		setPagePath({ commit }, o) {
			commit('SET_PAGE_PATH', o)
		},
		async setShopConfig({ commit }, o) {
			let res = await Vue.prototype.$http('shopConfig');
			if(res.code === 1){
				commit('SET_SHOP_CONFIG', res.data)
			}
		}
	},
	getters: {
		getPagePath: (state) => {
			return state.pagePath
		}
	}
}
