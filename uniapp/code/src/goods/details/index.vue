
<template>
    <view>
        <goods-details-tabs></goods-details-tabs>
        <goods-details-swiper></goods-details-swiper>
        <goods-details-price></goods-details-price>
        <goods-details-introduce></goods-details-introduce>
        <view class="like-box">- 猜你喜欢 -</view>
        <goods-list></goods-list>
        <goods-details-cart></goods-details-cart>
        
    </view>
</template>

<script>
export default {
    data() {
        return {};
    },
    onLoad() {},
    onShow() {},
    methods: {}
};
</script>
<style lang="scss">
page {
    width: 100%;
    height: 100%;
}
.like-box{
    font-size: 28rpx;
    color: #999999;
    text-align: center;
    margin-top: 32rpx;
}
</style>
