<template>
	<view class="ss-input-view" :class="['ss-' + type + '-view']" :ss-input-type="type">
		<slot name="before"></slot>
		<input v-if="type !== 'textarea'" ref="input" :disabled="disabled_" :confirm-type="confirmType" :maxlength="maxlength_" :focus="focus_" :type="inputType" :value="value" @input="onInput" class="ss-input"
		 :placeholder="placeholder" :password="type === 'password' && !showPassword" @focus="onFocus" @blur="onBlur" @confirm="onConfirm" placeholder-class="uni-input-placeholder" />
		<view class="ss-textarea" v-else>
			<textarea ref="input" :auto-height="autoHeight_" :disabled="disabled_" :confirm-type="confirmType" :maxlength="maxlength_" :focus="focus_" :type="inputType" :value="value" @input="onInput" class="ss-input-textarea"
			 :placeholder="placeholder" :password="type === 'password' && !showPassword" @focus="onFocus" @blur="onBlur" @confirm="onConfirm" placeholder-class="uni-input-placeholder"></textarea>
		</view>
		<view v-if="displayable_ && value.length" class="ss-input-icon" @click="display">
			<uni-icons :color="showPassword ? '#f92028' : '#d4d4d4'" type="eye"></uni-icons>
		</view>
		<view v-if="clearable_ && value.length || showClear_" class="ss-input-icon" @click="clear">
			<icon color="#d4d4d4" type="clear" :size="u35"></icon>
		</view>
		<slot></slot>
	</view>
</template>

<script>
	export default {
		props: {
			/**
			 * 输入类型
			 */
			type: {
				type: String,
				default: "text"
			},
			/**
			 * 值
			 */
			value: {
				type: [String, Number],
				default: ""
			},
			/**
			 * 占位符
			 */
			confirmType: {
				type: String,
				default: "done"
			},
			placeholder: String,
			/**
			 * 是否显示清除按钮
			 */
			clearable: {
				type: [Boolean, String],
				default: false
			},
			/**
			 * 是否自动增高，设置auto-height时，style.height不生效  type为textarea时才生效
			 */
			autoHeight: {
				type: [Boolean, String],
				default: false
			},
			/**
			 * 是否显示清除按钮
			 */
			showClear: {
				type: [Boolean, String],
				default: false
			},
			/**
			 * 是否显示密码可见按钮
			 */
			displayable: {
				type: [Boolean, String],
				default: false
			},
			arrows:{
				type: [Boolean, String],
				default: false
			},
			disabled: {
				type: [Boolean, String],
				default: false
			},
			sheetable: {
				type: [Boolean, String],
				default: false
			},
			maxlength: {
				type: [Number, String],
				default: 140
			},
			/**
			 * 自动获取焦点
			 */
			focus: {
				type: [Boolean, String],
				default: false
			},
			decimal: {
				type: [Number, String],
				default: 1
			},
			max: {
				type: [Number, String],
				default: 0 //0表示不限制
			},
			min: {
				type: [Number, String],
				default: 1
			}
		},
		model: {
			prop: 'value',
			event: 'input'
		},
		data() {
			return {
				/**
				 * 显示密码明文
				 */
				showPassword: false,
				u35: uni.upx2px(34)
			}
		},
		computed: {
			maxlength_() {
				return parseInt(this.maxlength);
			},
			decimal_() {
				return parseInt(this.decimal);
			},
			max_() {
				return this.$options.filters['price'](this.max, this.decimal_) * 1;
			},
			min_() {
				return this.$options.filters['price'](this.min, this.decimal_) * 1;
			},
			inputType() {
				return this.type === 'password' ? 'text' : (this.type === 'price' ? 'digit' : this.type)
			},
			sheetable_() {
				return String(this.sheetable) !== 'false'
			},
			clearable_() {
				return String(this.clearable) !== 'false'
			},
			autoHeight_() {
				return String(this.autoHeight) !== 'false'
			},
			showClear_() {
				return String(this.showClear) !== 'false'
			},
			displayable_() {
				return String(this.displayable) !== 'false'
			},
			arrows_() {
				return String(this.arrows) !== 'false' || this.type === 'select';
			},
			disabled_() {
				return String(this.disabled) !== 'false' || this.type === 'select';
			},
			focus_() {
				return String(this.focus) !== 'false'
			}
		},
		mounted() {
			// #ifdef H5
			if(uni.getSystemInfoSync().platform === 'ios' && this.decimal_ === 0){//修改金额类型为tel（整数数字--->针对ios）
				this.type === 'price' && (this.$refs.input.$el.getElementsByTagName('input')[0].type = 'tel');
			}
			// #endif
		},
		methods: {
			clear() {
				this.$emit('input', '')
				this.$emit('clear')
			},
			onSheet() {
				this.$emit('sheet')
			},
			display() {
				this.showPassword = !this.showPassword
			},
			onFocus() {
				this.$emit('focus');
			},
			onBlur(e) {
				this.onInput(e, true);
				this.$emit('blur', e.target.value);
				if (this.type === 'price') {
					uni.pageScrollTo({
						scrollTop: 0,
						duration: 0
					})
				}
			},
			onConfirm(e) {
				this.$emit('confirm', e);
			},
			onInput(e, isBlur) {
				let val = e.target.value;
				this.$emit('input', val);
				if (this.type === 'price') {
					var valIndexOf = val.indexOf('.') + 1;
					if(valIndexOf === val.length){//在ios端会进行小数点input输出
						if(isBlur || this.decimal_ === 0){
							val = this.$options.filters['price'](val, this.decimal_);
						}
					}else{
						val = /\d+(?:\.)?(?:\d*)?/.exec(val);
						val = val ? val[0] : '';
						if(isBlur || valIndexOf > 0){
							val = this.$options.filters['price'](val, this.decimal_);
						}
						if(val.length > 1 && val * 1 == 0){
							val = '';
						}
					}
					if (val && this.max_) {
						val = val > this.max_ ? this.max_ : val;
					}
					if(isBlur && val){
						val = val < this.min_ ? this.min_ : val;
					}
					this.$nextTick(() => {
						setTimeout(() => {
							this.$emit('input', val);
						})
					})
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.ss-input-view {
		display: flex;
		justify-content: center;
		align-items: center;
		height: 64rpx;
		position: relative;
		padding: 0 20rpx;
		/* #ifdef MP-WEIXIN */
		background-color: inherit;
		/* #endif */
		&.ss-textarea-view{
			height: 120rpx;
			line-height: 40rpx;
		}
		.password-icon{
			width: 38rpx;
			height: 38rpx;
			opacity: 1;
		}
		.ss-input, .ss-textarea {
			flex: 1;
			width: 100%;
			line-height: inherit;
			height: inherit;
			font-size: inherit;
			padding: 0;
			min-height: 0;
			background-color: transparent;
		}
		.ss-input-textarea{
			width: 100%;
		}
			
		&[ss-input-type="select"]{
			.ss-input-icon__arrows{
				@include abs(0, 0, 0, 0);
				z-index: 999;
				display: flex;
				align-items: center;
				justify-content: flex-end;
				width: 100%;
				padding-right: 20rpx;
				pointer-events: auto;
				
			}
		}
		.ss-input-icon {
			font-size: 0;
			width: 60rpx;
			text-align: center;
			line-height: 60rpx;
			display: flex;
			justify-content: center;
		}
		.ss-input{
			font-size: 24rpx;
			font-family: PingFangSC-Regular, PingFang SC;
			font-weight: 400;
			color: #333;
		}
		.uni-input-placeholder{
			color: #B3B3B3;
		}
	}
</style>
