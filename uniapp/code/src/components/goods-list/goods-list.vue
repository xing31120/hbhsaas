<template>
    <view class="goodsList">
        <view class="item" v-for="(item, index) in list" :key="index">
            <view class="img">
                <image :src="item.src" mode="aspectFill" />
            </view>
            <view class="name">
                <text class="hot" v-if="item.hot">{{item.hot}}</text>
                <text>{{item.name}}</text>
            </view>
            <view class="price">
                <text class="small">￥</text>
                <text>{{item.price}}</text>
            </view>
        </view>
    </view>
</template>
<script>
export default {
    props:{
        list:{
            type: Array,
            defult(){
                return []
            }
        }
    }
}
</script>
<style lang="scss" scoped>
.goodsList {
    display: flex;flex-wrap: wrap;justify-content: space-between;
    .item{
        border-radius: 16rpx;width: calc((100% - 24rpx)/2);background: #fff;overflow: hidden;margin-bottom: 24rpx;
        .price{
            line-height: 40rpx;font-size: 40rpx;color: #FA3F1E;font-weight: bold;margin: 0 16rpx 32rpx;
            .small{
                font-size: 24rpx;vertical-align: text-bottom;font-weight: normal;
            }
        }
        .img{
            width: 100%;height: 0;padding-bottom: 100%;position: relative;
            image{
                width: 100%;height: 100%;position: absolute;top: 0;left: 0;
            }
        }
        .name{
            line-height: 40rpx;text-overflow: ellipsis;overflow: hidden;margin: 16rpx;color: #333;-webkit-line-clamp: 2; -webkit-box-orient: vertical;display: -webkit-box;
            .hot{
                display: inline-block;height: 30rpx;line-height: 30rpx;text-align: center;color: #fff;background: #FA3F1E;border-radius: 4rpx;padding: 0 7rpx;
            }
        }
    }
    
}
</style>
