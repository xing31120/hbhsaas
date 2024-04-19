<template>
    <view class="slideMenu">
        <view class="item">
            <image src="@/static/home/kefu.png" mode="aspectFill" />
        </view>
        <view class="item">
            <image src="@/static/home/share.png" mode="aspectFill" />
        </view>
    </view>
</template>
<script>
export default {
    
}
</script>
<style lang="scss" scoped>
.slideMenu{
    position: fixed;bottom: 98rpx;right: 40rpx;
    .item{
        width: 96rpx;height: 96rpx;overflow: hidden;border-radius: 50%;margin-bottom: 40rpx;
        image{
            width: 100%;height: 100%;
        }
    }
}
</style>
