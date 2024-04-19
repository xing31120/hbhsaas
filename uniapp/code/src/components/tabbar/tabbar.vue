<template>
	<view class="clear-tabbar-box">
		<view class="tabbar-box flex ss-underline" :style="{backgroundColor: bgColor}">
			<view class="flex-1" v-for="(item, index) in list_" :key="index" @click="changeTab(item.pagePath, index)">
				<view class="tabbar__image">
					<image :src="selectIndex_ === index ? item.selectedIconPath : item.iconPath | ossImg"></image>
				</view>
				<view class="tabbar__text" :style="{ color: selectIndex_ === index ? selColor : color }">{{item.text}}</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { mapState } from 'vuex'
	export default {
		data() {
			return {
				tabList: []
			}
		},
		props: {
			bgColor: {
				type: String,
				default: '#fff'
			},
			color: {
				type: String,
				default: '#666'
			},
			selColor: {
				type: String,
				default: '#fa3f1e'
			},
		},
		computed: {
			...mapState({
				shopConfig_: state => state.config.shopConfig
			}),
			selectIndex_() {
				return this.list_.findIndex(o => o.pagePath === this.$Route.path);
			},
			list_() {
				let tabbarArr = []
				if (!this.shieldReview) {
					// -- 审核 模式
					tabbarArr = [
						{
							pagePath: 'pages/index/client',
							iconPath: 'newTabbar/tabBar_8.png',
							selectedIconPath: 'newTabbar/tabBar_cur_8.png',
							text: '商城'
						},
						{
							pagePath: 'pages/cart/cart',
							iconPath: 'newTabbar/tabBar_3.png',
							selectedIconPath: 'newTabbar/tabBar_cur_3.png',
							text: '购物车'
						},
						{
							pagePath: 'pages/user/user',
							iconPath: 'newTabbar/tabBar_4.png',
							selectedIconPath: 'newTabbar/tabBar_cur_4.png',
							text: '我的'
						}
					]
				} else if (this.userInfo.index_style == 2) {
					// b端 -- tabbar
					tabbarArr = [
						{
							pagePath: this.userInfo.shop_id ? 'pages/shop/shopHome/shopHome?ru_id=${this.userInfo.id}' : 'pagesB/applyShop/applyShop',
							iconPath: 'newTabbar/tabBar_1.png',
							selectedIconPath: 'newTabbar/tabBar_cur_1.png',
							text: '首页'
						},
						{
							pagePath: 'pagesB/index/business',
							iconPath: 'newTabbar/tabBar_9.png',
							selectedIconPath: 'newTabbar/tabBar_cur_9.png',
							text: '获客'
						},
						{
							pagePath: 'pages/index/client',
							iconPath: 'newTabbar/tabBar_8.png',
							selectedIconPath: 'newTabbar/tabBar_cur_8.png',
							text: '商城'
						},
						{
							pagePath: 'pages/user/user',
							iconPath: 'newTabbar/tabBar_4.png',
							selectedIconPath: 'newTabbar/tabBar_cur_4.png',
							text: '我的'
						}
					]
				} else {
					// c端 -- tabbar
					tabbarArr = [
						{
							pagePath: 'pages/index/client',
							iconPath: 'newTabbar/tabBar_1.png',
							selectedIconPath: 'newTabbar/tabBar_cur_1.png',
							text: '首页'
						},
						{
							pagePath: 'pagesB/h5/loan',
							iconPath: 'newTabbar/tabBar_6.png',
							selectedIconPath: 'newTabbar/tabBar_cur_6.png',
							text: '家装贷'
						},
						{
							pagePath: 'pages/cart/cart',
							iconPath: 'newTabbar/tabBar_3.png',
							selectedIconPath: 'newTabbar/tabBar_cur_3.png',
							text: '购物车'
						},
						{
							pagePath: 'pages/user/user',
							iconPath: 'newTabbar/tabBar_4.png',
							selectedIconPath: 'newTabbar/tabBar_cur_4.png',
							text: '我的'
						}
					]
				}
				if (this.shieldReview && this.shopConfig_) {
					let newObj = {
						pagePath: 'pagesB/explosiveVideo/list',
						iconPath: 'newTabbar/tabBar_5.png',
						selectedIconPath: 'newTabbar/tabBar_cur_5.png',
						text: '爆品'
					}
					let status = this.shopConfig_.mjb_wxapp_module_hidden.indexOf('mjbVideo') == -1
					if (status) {
						let index = this.userInfo.index_style == 2 ? 2 : 1
						tabbarArr.splice(index, 0, newObj)
					}
				}
			
				return tabbarArr
			}
		},
		methods: {
			changeTab(url, index) {
				if(this.selectIndex_ === index) return ;
				this.$Router.replace({
					path: '/' + url
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.clear-tabbar-box{
		@include iosSafeArea(height, 50px, bottom);
		.tabbar-box{
			@include fixed(null, 0, 0, 0);
			height: 50px;
			@include iosSafeArea(padding, 0, bottom, bottom);
			&::after{
				bottom: auto;
				top: 0;
			}
			.flex-1{
				padding-top: 5px;
				height: 100%;
			}
			.tabbar__image{
				image{
					width: 20px;
					height: 20px;
					display: block;
					margin: 0 auto;
				}
			}
			.tabbar__text{
				text-align: center;
				display: block;
				font-size: 12px;
				line-height: 20px;
			}
		}
	}
</style>
