// import { mapGetters } from 'vuex'
// import { pageTo } from '@/utils/util.js'
import { getMiniAppUrl } from '@/utils'
export default {
	data() {
		return {
			// 全局混入，插入全局常量
			constant: this.$constant,
			// 全局混入，插入全局工具函数
			$util: this.$util,
			// 全局混入，插入全局跳转函数
			// pageTo: pageTo
		}
	},
	// computed: {
	// 	...mapGetters({ userInfo: 'getUser' })
	// },
	// 页面生命周期才触发
	onLoad: function () {
		// 设置当前页面路径
        this.$store.dispatch('config/setPagePath', this.$utilFn.getMiniAppUrl(this.$Route.query))
		
	},
	mounted() {}
}
