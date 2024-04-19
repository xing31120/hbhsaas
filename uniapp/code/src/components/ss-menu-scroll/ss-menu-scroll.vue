<template>
	<view class="ss-menu-scroll-box">
		<slot name="before"></slot>
		<scroll-view class="ss-menu-scroll line-1" :class="{'ss-menu-scroll-horizontal': scrollX_, 'ss-menu-scroll-home': mode === 'homeIndex'}" :scroll-x="scrollX_" :scroll-y="!scrollX_" scroll-with-animation :scroll-into-view="currentView_">
			<view class="ss-menu-scroll-list">
				<view class="ss-menu-scroll-item" @click="changeType(index)" :id="'type' + index" v-for="(item, index) in config" :key="index" :class="{'ss-menu-scroll-item__active' : menuIndex === index}">
					<text class="ss-menu-scroll-text">{{item[label]}}</text>
				</view>
			</view>
		</scroll-view>
		<slot></slot>
	</view>
</template>

<script>
	export default {
        data() {
           return {
               menuIndex: 0
           }
        },
		props: {
			config: {
				type: Array,
				default: () => []
			},
			value: {
				type: Number,
				default: 0
			},
			scrollX: {
				type: [ String, Boolean ],
				default: true
			},
			label: {
				type: String,
				default: 'label'
			},
            mode: {
				type: String,
				default: 'normal'
			},
		},
        watch: {
            value(val) {
                this.changeType(val);
            }
        },
		computed: {
			scrollX_() {
				return String(this.scrollX) === 'false' ? false : true;
			},
			currentView_() {
				let typeIndex = this.value - 2;
				typeIndex = typeIndex < 0 ? 0 : typeIndex;
				return 'type' + typeIndex;
			}
		},
		methods: {
			changeType(index){
				if(this.menuIndex === index) return ;
                this.menuIndex = index;
				this.$emit('input', index);
				this.$emit('change', this.config[index]);
			}
		}
	}
</script>

<style lang="scss" scoped>
.ss-menu-scroll-box{
	width: 100%;
	height: 100%;
	position: relative;
    display: flex;
    align-items: center;
	.ss-menu-scroll{
		flex: 1;
        width: 50%;
        height: 100%;
	}

	.ss-menu-scroll-list{
		width: 100%;
	}

	.ss-menu-scroll-item{
		text-align: center;
		position: relative;
		@include ellipsis();
		display: block;
		padding: 22rpx 15rpx;
		&.ss-menu-scroll-item__active{
			.ss-menu-scroll-text{
				color: #fff;
				background: #FA3F1E;
				border-radius: 25px;
			}
		}
	}
	.ss-menu-scroll-text{
		display: block;
		font-weight:400;
		color: #666666;
		line-height:50rpx;
		height:50rpx;
		font-size: 28rpx;
        position: relative;
		font-family: PingFangSC-Regular, PingFang SC;
	}

	.ss-menu-scroll-horizontal{
		.ss-menu-scroll-list{
            white-space: nowrap;
			width: 100%;
		}
		.ss-menu-scroll-list-3{
			padding: 0 40rpx;
		}

		.ss-menu-scroll-list-4{
			padding: 0 10rpx;
		}
		.ss-menu-scroll-item{
			display: inline-block;
            color: #333333;
            padding: 0 24rpx;
            font-weight: 400;
            &.ss-menu-scroll-item__active{
                .ss-menu-scroll-text{
                    color: #333;
                    font-weight: 500;
                    background: transparent;
                    border-radius: 0;
                }
                &::after{
                    content: '';
                    width: 48rpx;
                    height: 4rpx;
                    background: #333333;
                    border-radius: 3px;
                    @include abs(null, 50%, 0);
                    transform: translateX(50%);
                }
            }
		}
	}
    .ss-menu-scroll-home{
		.ss-menu-scroll-item{
            display: inline-block;
            color: #fff;
            padding: 0 20rpx;
            font-weight: 400;
            .ss-menu-scroll-text{
                color: #fff;
                line-height:88rpx;
                height:88rpx;
            }
            &.ss-menu-scroll-item__active{
                .ss-menu-scroll-text{
                    color: #fff;
                    font-size: 32rpx;
                    font-weight: 500;
                    background: transparent;
                    border-radius: 0;
                }
                &::after{
                    content: '';
                    width: 30rpx;
                    height: 14rpx;
                    background: url(../../static/home/active.png) center bottom / 100% auto no-repeat;
                }
            }
        }
    }
}
</style>
