<template>
	<view class="ss-modal-body" :class="{'ss-modal-active' : animation, 'ss-modal-full': mode === 'insert' || position === 'middle', 'ss-modal-hastabbar': hasTabbar}">
		<view class="ss-modal" :class="'ss-modal-' + position +' ' + 'ss-modal-' + mode" @touchmove.stop.prevent>
			<text class="ss-modal-close" @click="hide()" v-if="showClose_"></text>
			<slot></slot>
		</view>
		<view v-if="mask_" class="uni-mask" catchtouchmove="true" @click.stop="maskClose()" @touchmove.stop.prevent></view>
	</view>
</template>

<script>
	export default {
		data () {
			return {
				animation: false
			}
		},
		props: {
			/*
			* 参数说明（定位）
			*/
			position: {//可能值  top  left  right bottom middle
				type: String,
				default: 'middle'
			},
			/*
			* 参数说明
			* full 宽度100%
			* insert 80%宽度内联小框
			* cover 宽度高度100%
			*/
			mode: {
				type: String,
				default: 'insert'
			},
			mask: {
				type: [Boolean, String],
				default: true
			},
			hasTabbar: {
				type: Boolean,
				default: false
			},
			showClose:{
				type: [Boolean, String],
				default: true
			},
			maskabled: {
				type: [Boolean, String],
				default: false
			}
		},
		computed: {
			mask_() {
				return String(this.mask) === 'false' ? false : true;
			},
			showClose_() {
				return String(this.showClose) === 'false' ? false : true;
			},
			maskabled_() {
				return String(this.maskabled) === 'false' ? false : true;
			}
		},
		watch: {
			animation(val) {
				this.$emit('change', val);
			}
		},
		methods: {
			moveHandle() {
				return ;
			},
			show () {
				this.animation = true;
				return true;
			},
			maskClose() {
				if(!this.maskabled_) return ;
				this.hide();
			},
			hide () {
				this.animation = false;
				return false;
			},
			toggle () {
				return !this.animation ? this.show() : this.hide()
			},
			modalFun(pro) {
				return this[pro]();
			}
		}
	}
</script>

<style lang="scss" scoped>
	// 弹窗公用样式
	.ss-modal-body{
		opacity: 0;
		@include fixed(0, 0, 0, 0);
		pointer-events: none;
		transition: all .2s cubic-bezier(0.65, 0.05, 0.36, 1);
		z-index: 999;
		&.ss-modal-full{
			transform: scale(1.2);
		}
		&.ss-modal-active{
			transform: scale(1);
			pointer-events: auto;
		}
		/* #ifndef H5 */
		&.ss-modal-hastabbar{
			@include iosSafeArea(bottom, 50px, bottom);
		}
		/* #endif */
	}
	.uni-mask{
		position: fixed;
		z-index: 999;
		top: 0;
		right: 0;
		left: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		z-index: 998;
	}
	.ss-modal{
		position: fixed;
		z-index: 999;
		max-height: 100%;
		transition: inherit;
		/deep/ .gmy-float-touch{
			display: none;
		}
	}
	.ss-modal-close{
		width: 45rpx;
		height: 85rpx;
		@include abs(-85rpx, 30rpx);
		background: url(../../static/img/culturalActivity/tc_close_icon@2x.png) center center / 100% 100% no-repeat;
	}
	.ss-modal-middle{
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		background: none;
		box-shadow: none;
	}
	.ss-modal-cover{
		width: 100%;
		height: 100%;
		left: 0;
		top: 0;
		transform: translate(0, 0);
		opacity: 0;
	}
	.ss-modal-top{
		left: 50%;
		z-index: 98;
		width: 100%;
		height: auto;
		transform: translate(-50%, -100%);
		& + .uni-mask{
			z-index: 97;
		}
	}
	.ss-modal-cover.ss-modal-top{
		transition: all .3s linear;
		left: 0;
		transform: translate(0, -100%);
		height: 100%;
		top: 0;
		z-index: 999;
	}
	.ss-modal-full{
		width: 100%;
		// width: 100vw;
		width: calc(100% + 3px);
		// width: calc(100vw + 2px);//清除translate带来了计算误差
		left: 0;
	}
	.ss-modal-full.ss-modal-top{
		transform: translate(0, -200%);
	}
	.ss-modal-full.ss-modal-bottom{
		transform: translate(0, 120%);
		transition: inherit;
	}
	.ss-modal-full.ss-modal-middle{
		transform: translate(0, 120%);
		transition: inherit;
	}
	.ss-modal-right{
		right: 0;
		max-width: 80%;
	}
	.ss-modal-left{
		left: 0;
		max-width: 80%;
	}
	.ss-modal-insert{
		min-width: 500rpx;
		min-height: 380rpx;
		max-width: 102%;
		max-height: 95%;
		transform: translate(-50%, 0);
	}
	.ss-modal-middle,.ss-modal-insert{
		transform: translate(-50%, -50%);
	}
	.ss-modal-bottom{
		bottom: 0;
		min-height: 0;
	}
	.ss-modal-bottom.ss-modal-insert{
		left: 50%;
		transform: translate(-50%, 100%);
	}
	.ss-modal-body{
		opacity: 0;
		pointer-events: none;
	}
	.ss-modal-active{
		opacity: 1;
		pointer-events: auto;
		.ss-modal-top{
			top: 0;
			transform: translate(-50%, 0);
		}
		.ss-modal-full.ss-modal-top{
			transform: translate(0, 0);
		}
		.ss-modal-full.ss-modal-middle{
			transform: translate(0, -50%);
		}
		.ss-modal-bottom{
			transform: translate(0, 0);
		}
		.ss-modal-bottom.ss-modal-insert{
			transform: translate(-50%, 0);
		}
		.ss-modal-cover{
			opacity: 1;
		}
		.ss-modal-cover.ss-modal-top{
			top: 0;
		}
	}
</style>
