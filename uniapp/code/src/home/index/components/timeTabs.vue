<template>
  <view class="timeTabs">
    <scroll-view :scroll-x="true" :scroll-with-animation="true" :scroll-left="scrollLeft > containerWidth / 5 ? scrollLeft : 0">
      <view class="item" v-for="(item, index) in list" :key="index" :class="{active: active === index}" @tap="choice(item,index)">
        <view class="label">{{item.label}}</view>
        <view class="name">{{active === index ? '抢购中' : '即将开始'}}</view>
      </view>
    </scroll-view>
  </view>
</template>
<script>
export default {
  props: {
    list:{
      type: Array,
      default(){
        return []
      }
    },
    value: {
      type: Number,
      default: 0
    }
  },
  computed:{
    active: {
      get(){
        return this.value
      },
      set(val){
        this.$emit('input', val)
      }
    },
      scrollLeft: {
        get(){
            return this.value*this.itemLength
        }
      }
  },
  data() {
    return {
      containerWidth: '',
      itemLength: 0
    }
  },
  methods: {
    choice(item,index){
      this.active = index
    },
    // 获取左移动位置
      getTabItemWidth() {
          let query = uni.createSelectorQuery()
              // #ifndef MP-ALIPAY
              .in(this)
              // #endif
          // 获取容器的宽度
          query.select('.timeTabs').boundingClientRect((data) => {
              if (!this.containerWidth && data) {
                  this.containerWidth = data.width
              }
              }).exec()
          // 获取所有的 tab-item 的宽度
          
          query.selectAll('.timeTabs .item').boundingClientRect((data) => {
              for (let i = 0; i < data.length; i++) {
                  const item = data[i]
                  if (!this.itemLength || this.itemLength > item.width ) {
                      this.itemLength = item.width
                  }
              }
          }).exec()
      }
  },
  mounted() {
    this.$nextTick(() => {
      this.getTabItemWidth()
    })
  }
}
</script>
<style lang="scss" scoped>
.timeTabs{
  white-space: nowrap;font-size: 0;border-bottom: solid 1rpx #EDEDED;height: 92rpx;margin-bottom: 35rpx;
  .item{
    position: relative;margin: 0 44rpx;display: inline-block;height: 92rpx;color: #999999;text-align: center;transition: all .3s;
    .label{
      font-size: 32rpx;line-height: 1;margin-bottom: 10rpx;
    }
    .name{
      font-size: 22rpx;font-weight: 500;line-height: 30rpx;
    }
    &.active{
      color: #FA3F1E;font-weight: bold;
      .name{
        font-weight: 500;
      }
    }
    ::after{
      bottom: 0;height: 0;width: 0;display: inline-block;content: '';background: #fff;transition: all .3s;position: absolute;left: 50%;transform: translateX(-50%);
    }
    &.active::after{
      height: 4rpx;width: 80rpx;background: #FA3F1E;position: absolute;left: 50%;bottom: 1rpx;transform: translateX(-50%);content: '';
    }
  }
}
</style>
