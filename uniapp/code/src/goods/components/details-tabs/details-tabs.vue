<template>
    <view class="goods-header-tabs flex-ajcenter">
        <text class="uname" :class="{'active': active === 'goods'}" @click="handleSelect('goods')">商品</text>
        <text class="uname" :class="{'active': active === 'details'}" @click="handleSelect('details')">商品</text>
        <text class="uname" :class="{'active': active === 'recommend'}" @click="handleSelect('recommend')">商品</text>
        <text class="uname" @click="handleSelect('')">分享</text>
    </view>
</template>
<script>
export default {
    data() {
        return {
            active: 'goods'
        }
    },
    methods: {
        handleSelect(val){
            this.active = val
        }
    },
}
</script>
<style lang="scss" scoped>
.goods-header-tabs {
    width: 100%;
    height: 80rpx;
    background: #ffffff;
    font-size: 28rpx;
    .uname {
        padding: 0 28rpx;
        color: #000000;
        &.active {
            color: #fa3f1e;
            position: relative;
            &::before {
                content: "";
                position: absolute;
                bottom: -6rpx;
                left: 50%;
                transform: translateX(-50%);
                width: 40rpx;
                height: 4rpx;
                background: #fa3f1e;
            }
        }
    }
}
</style>
