<template>
  <view class="card">
    <view class="top">
      <view class="title">
        <text class="h1">{{title}}</text>
        <text class="small" v-if="label">{{label}}</text>
      </view>
      <view class="timeOut" v-if="timeOut">
        <view class="session">{{session}}</view>
        <view class="item hour">{{hour}}</view>
        <view class="point">
          <text>:</text>
        </view>
        <view class="item minutes">{{minutes}}</view>
        <view class="point">
          <text>:</text>
        </view>
        <view class="item seconds">{{seconds}}</view>
      </view>
      <view class="more">
        <text>更多</text>
      </view>
    </view>
    <scroll-view :scroll-x="true" class="content" :scroll-with-animation="true">
      <slot name="content"/>
    </scroll-view>
  </view>
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      default: null
    },
    label: {
      type: String,
      default: null
    },
    timeOut: {
      type: Number,
      default: 0
    },
    path: {
      type: String,
      default: null
    },
    session: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      setTime: null
    }
  },
  methods: {
    openCountdown(){ // 开启倒计时
      this.setTime = setInterval(() => {
        
      }, 1000);
    }
  },
  computed: {
    hour: {
      get(){
        const hour = Math.floor(this.timeOut / 3600)
        return  hour < 10 ? '0' + hour : hour
      }
    },
    minutes: {
      get(){
        const minutes = Math.floor(( this.timeOut % 3600 ) / 60);
        return minutes < 10 ? '0' + minutes : minutes
      }
    },
    seconds: {
      get(){
        const seconds = Math.floor((this.timeOut % 3600) % 60);
        return seconds < 10 ? '0' + seconds : seconds
      }
    }
  }
};
</script>
<style lang="scss" scoped>
.card{
  margin: 0 24rpx;background: #fff;border-radius: 16rpx;
  .top{
    height: 94rpx;display: flex;align-items: center;padding: 0 24rpx;
    .title{
      line-height: 32rpx;height: 32rpx;
      .h1{
        font-size: 32rpx;color: #333;font-weight: 600;
      }
      .small{
        font-size: 20rpx;color: #333;padding-left: 16rpx;
      }
    }
    .more{
      font-size: 24rpx;color: #333;line-height: 24rpx;margin-left: auto;
      &::after{
        content: ">";font-size: 24rpx;color: #333;padding-left: 16rpx;display: inline; 
      }
    }
    .timeOut{
      display: flex;padding: 0 16rpx;height: 32rpx;
      .session{
        font-size: 20rpx;color: #333;padding-right: 13rpx;line-height: 32rpx;
      }
      .item{
        width: 32rpx;color: #fff;height: 32rpx;border-radius: 4rpx;background: #FA3F1E;font-size: 24rpx;text-align: center;line-height: 32rpx;
      }
      .point{
        width: 20rpx;height: 32rpx;text-align: center;line-height: 32rpx;color: #FA3F1E;
      }
    }
  }
  .content{
    padding: 6rpx 0 24rpx;
  }
}
</style>
