<template>
	<view class="page">
		<view class="navTop">
			<view class="content">
				<homeSerarch :path="searchPath" :title="shopName" :scrollTop="scrollTop" v-model="isFixed"/>
				<tabs :tabs="classify" v-model="active" iconBg="#FA3F1E" :isFixed="isFixed"/>
			</view>
		</view>
		<view class="banner">
			<banner :list="bannerList" />
		</view>
		<view class="matrixMenu">
			<matrixMenu :list="modelMenu" />
		</view>
		<view class="middleAd">
			<image src="@/static/home/ad.png" mode="aspectFill" />
		</view>
		<!-- 秒杀 -->
		<view class="secondsKill">
			<card title="限时秒杀" session="8点场" :timeOut="3680">
				<template slot="content">
					<timeTabs :list="session" v-model="sessionIndex"/>
					<scroll-goods :list="secondsKillGoods"/>
				</template>
			</card>
		</view>
		<!-- 爆款 -->
		<view class="secondsKill">
			<card title="热销爆款">
				<template slot="content">
					<scroll-goods :list="hotGoods" :hot="true"/>
				</template>
			</card>
		</view>
		<!-- 分类 -->
		<view class="classify">
			<tabs :tabs="classify" border v-model="active" color="#333"/>
		</view>
		<view class="goodsList">
			<!-- <goods-list :list="goodsList"/> -->
		</view>
		<!-- 有小菜单 -->
		<slideMenu />
	</view>
</template>
<script>
	import homeSerarch from './components/serarch'
	import tabs from './components/tabs'
	import banner from './components/banner'
	import matrixMenu from './components/matrixMenu'
	import timeTabs from './components/timeTabs'
	import slideMenu from './components/slide-menu'
	// import { scrollGoods } from '@/components/scroll-goods/scroll-goods'
	export default {
		components:{
			homeSerarch, tabs, banner, matrixMenu, timeTabs, slideMenu//, scrollGoods
		},
		data() {
			return {
				isFixed: false,
				scrollTop: 0,
				searchPath: '', // 搜素页面路径
				shopName: '我是店铺名称',
				classify: [
					{ name: '首页', path: '' },
					{ name: '家用电器', path: '' },
					{ name: '套餐包', path: '' },
					{ name: '卫浴工具', path: '' },
					{ name: '瓷砖地板', path: '' },
					{ name: '瓷砖地板', path: '' },
					{ name: '瓷砖地板', path: '' }
				],
				active: 0,
				bannerList: [
					{ path: '', src: '' },
					{ path: '', src: '' },
					{ path: '', src: '' },
					{ path: '', src: '' },
					{ path: '', src: '' },
				],
				modelMenu: [
					{ name: '家具建材', path: '', icon: '' },
					{ name: '装修攻略', path: '', icon: '' },
					{ name: '家装贷', path: '', icon: '' },
					{ name: '精选案例', path: '', icon: '' },
				],
				session: [
					{ label: '08:00' },
					{ label: '12:00' },
					{ label: '16:00' },
					{ label: '20:00' },
					{ label: '00:00' }
				],
				sessionIndex: 0,
				secondsKillGoods: [
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
				],
				hotGoods: [
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', oldPrice: '599.00',src: '' },
				],
				goodsList: [
					{ name: '潜水艇304不锈钢潜水艇304不锈钢潜水艇304不锈钢潜水艇304不锈钢', price: '399.00', src: '', hot: '热销' },
					{ name: '潜水艇304不锈钢', price: '399.00', src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', src: '' },
					{ name: '潜水艇304不锈钢', price: '399.00', src: '' }
				],
				previous: 0
			}
		},
		onLoad() {},
		onShow() {
			this.init()
		},
		methods: {
			init() {
			}
		},
		onPageScroll(obj){
			let now = Date.now();
			if (now - this.previous > 17) {
				this.scrollTop = obj.scrollTop
				this.previous = now;
			}
		},
	}
</script>
<style>
page{
	background: #F5F5F5;
}
/* image{
	background: #ccc;border-radius: 4rpx;
} */
view{
	box-sizing: unset;
}
::-webkit-scrollbar{
	width: 0;height: 0; color: transparent;
}
</style>
<style lang="scss" scoped>
.navTop{
	height: 0;padding-bottom: calc(64.6667% - 44rpx);background: #FA3F1E;position: relative;
	&::after{
		content: '';position: absolute;bottom: -44rpx;height: 66rpx;background: #FA3F1E;border-radius: 0 0 50% 50%;display: block;left: 0;width: 100%;
	}
	.content{
		position: absolute;top: 52rpx;left: 0;width: 100%;height: calc(100% - 52rpx);
	}
}
.banner{
	width: calc(100% - 48rpx);margin: -118rpx  24rpx 0;position: relative;z-index: 20;border-radius: 16rpx;overflow: hidden;
}
.matrixMenu{
	margin: 24rpx 24rpx 0;background: #fff;border-radius: 16rpx;height: 169rpx;
}
.middleAd{
	margin: 24rpx;height: 140rpx;
	image{
		height: 100%;width: 100%;border-radius: 16rpx;
	}
}
.secondsKill{
	margin-bottom: 20rpx;
}
.goodsList{
	margin: 0 24rpx;
}
.classify{
	margin: 0 0 10rpx;
}
</style>
