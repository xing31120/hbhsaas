<template>
	<view class="ss-content home-category-content">
		<view class="search-box ss-underline" id="searchRef">
			<global-ss-input class="search-box__input" placeholder="输入搜索关键词">
				<image class="search-box__image" src="~@/static/image/home/search.png" slot="before" mode="widthFix"></image>
			</global-ss-input>
		</view>
		<view class="flex category-box" :style="{height: scrollHeight}">
			<view class="category-left-box">
				<ss-menu-scroll v-model="menuIndex" scrollX="false" label="cat_name" :config="menuList" />
			</view>
			<view class="category-right-box flex-1">
				<scroll-view scroll-y class="category-scroll">
					<view class="category-scroll-content">
						<view class="category-right__banner" :style="{backgroundImage: 'url(' + curCategory_.touch_catads + ')'}"></view>
						<view class="category-right__list">
							<view class="category-right__item" v-for="category in categoryData" :key="category.cat_id">
								<view class="category-right__item_title">{{category.cat_name}}</view>
								<view class="category-list clearfix">
									<view class="category-item" v-for="item in category.child" :key="item.cat_id">
										<view class="category-item__image" :style="{backgroundImage: 'url(' + item.touch_icon + ')'}"></view>
										<view class="category-item__text">{{item.cat_name}}</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>
		</view>
		<global-tabbar id="tabBarRef" />
	</view>
</template>
<script>
	export default {
		data() {
			return {
				menuIndex: -1,
				menuList: [],
				categoryData: [],
				scrollHeight: '0px'
			}
		},
		onLoad() {
            console.timeEnd('star')
			this.calcELHeight();
			this.getCatalogList();
		},
		computed: {
			curCategory_() {
				return this.menuList[this.menuIndex] || {};
			}
		},
		watch: {
			menuIndex(val) {
				let id = this.curCategory_.cat_id;
				id && this.getCatalogList(id);
			}
		},
		methods: {
			async calcELHeight() {
				let res1 = await this.$utilFn.getElSize('searchRef', this)
				let res2 = await this.$utilFn.getElSize('tabBarRef', this)
				this.scrollHeight = uni.getSystemInfoSync().windowHeight - res1.height - res2.height + 'px';
			},
			async getCatalogList(id) {
				id && (this.categoryData = []);
				let res = await this.$http('catalogList', id);
				console.log(res)
				if(res.code === 1){
					if( id !== undefined){
						this.categoryData = res.data;
					}else{
						this.menuIndex = 0;
						this.menuList = res.data;
					}
				}
			}
		}
	}
</script>

<style lang="scss">
	.home-category-content{
		@include iosSafeArea(padding, 0px , bottom, bottom);
		.category-right-box{
			.category-scroll{
				height: 100%;
			}
			.category-scroll-content{
				padding: 24rpx 24rpx 0;
			}
			.category-right__item{
				margin-top: 24rpx;
				padding: 12rpx;
				background-color: #fff;
				border-radius: 16rpx;
				.category-list{
					.category-item{
						padding: 12rpx;
						width: 33.33333333%;
						float: left;
					}
					.category-item__image{
						width: 100%;
						height: 142rpx;
						background: #fff center center / contain no-repeat;
					}
					.category-item__text{
						margin-top: 8rpx;
						font-size: 24rpx;
						font-family: PingFangSC-Regular, PingFang SC;
						font-weight: 400;
						color: #666666;
						line-height: 33rpx;
						text-align: center;
						@include ellipsis();
					}
				}
				.category-right__item_title{
					font-size: 28rpx;
					font-family: PingFangSC-Medium, PingFang SC;
					font-weight: 500;
					color: #333333;
					line-height: 40rpx;
					padding: 12rpx;
				}
			}
			.category-right__banner{
				background: #8D8D8D center center / cover no-repeat;
				height: 180rpx;
				border-radius: 16rpx;
			}
		}
		.category-box{
			height: 0;
			& > view{
				height: 100%;
			}
			.category-left-box{
				width: 180rpx;
				background-color: #fff;
			}
		}
		.search-box{
			background-color: #fff;
			padding: 16rpx 24rpx;
			.search-box__input{
				height: 56rpx;
				line-height: 56rpx;
				background: #F5F5F5;
				border-radius: 8rpx;
			}
			.search-box__image{
				width: 30rpx;
				margin-right: 15rpx;
			}
		}
	}
</style>
