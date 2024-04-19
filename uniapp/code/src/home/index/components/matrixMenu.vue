<template>
    <view class="matrixMenu">
        <view class="item" v-for="(item, index) in list" :key="index" @tap="jump(item.path)">
            <image :src="item.icon" mode="aspectFill" />
            <text>{{item.name}}</text>
        </view>
    </view>
</template>
<script>
export default {
    props: {
        list: {
            type: Array,
            default() {
                return []
            }
        }
    },
    methods: {
        jump(url){
            uni.navigateTo({
                ulr
            });
        }
    },
}
</script>
<style lang="scss" scoped>
.matrixMenu{
    display: flex;justify-content: space-between;padding: 32rpx 40rpx 0;
    .item{
        text-align: center;
        image{
            width: 56rpx;height: 56rpx;margin: 0 auto 16rpx;display: block;
        }
        text{
            font-size: 24rpx;color: #333333;line-height: 33rpx;font-weight: 500;
        }
    }
}
</style>
