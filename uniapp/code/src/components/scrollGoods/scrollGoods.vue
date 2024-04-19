<template>
  <view class="goodsList">
    <scroll-view :scroll-x="true" :scroll-with-animation="true">
      <view class="item" v-for="(item, index) in list" :key="index" :class="{active: active === index}" @tap="choice(item,index)">
        <image class="goods" :src="item.src" mode="aspectFill" />
        <view class="name">
          <text>{{item.name}}</text>
        </view>
        <view class="price">
          <image v-if="hot" class="hot" src="" mode="widthFix" />
          <text class="small">￥</text>
          <text>{{item.price}}</text>
        </view>
        <view class="oldPrice" v-if="item.oldPrice">
          <text class="small">￥</text>
          <text>{{item.oldPrice}}</text>
        </view>
      </view>
    </scroll-view>
  </view>
</template>
<script>
export default {
  props: {
    hot: {
      type: Boolean,
      default: false
    },
    list:{
      type: Array,
      default(){
        return []
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.goodsList{
  white-space: nowrap;font-size: 0;padding-top: 30rpx;padding: 0 12rpx 0;
  .item{
    margin: 0 12rpx;position: relative;width: 172rpx;display: inline-block;
    .goods{
      width: 172rpx;height: 172rpx;margin-bottom: 10rpx;
    }
    .name{
      height: 34rpx;font-size: 24rpx;line-height: 34rpx;color: #333;display: block;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;
    }
    .price{
      padding-top: 30rpx;line-height: 30rpx;font-size: 30rpx;color: #FA3F1E;font-weight: bold;
      .small{
        font-size: 20rpx;vertical-align: text-bottom;font-weight: normal;
      }
      .hot{
        width: 23rpx;margin-right: 8rpx;max-height: 30rpx;
      }
    }
    .oldPrice{
      font-size: 22rpx;color: #999999;text-decoration: line-through;margin-top: 10rpx;
    }
  }
}
</style>
