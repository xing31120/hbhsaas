/** 基本用户信息 */
export default {
	namespaced: true,
	state: {
		pagePath: null
	},
	mutations: {
		SET_PAGE_PATH(state, o) {
			state.pagePath = o
		}
	},
	actions: {
		setPagePath({ commit }, o) {
			commit('SET_PAGE_PATH', o)
		}
	},
	getters: {
		getPagePath: (state) => {
			return state.pagePath
		}
	}
}
