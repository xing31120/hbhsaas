<template>
    <view class="details-swiper-box">
        <swiper
            class="details-swiper"
            :autoplay="autoplay"
            :interval="interval"
            :duration="duration"
            @change="handleChange"
        >
            <swiper-item v-for="(item, index) in background" :key="index">
                <view class="swiper-item" :class="item"></view>
            </swiper-item>
        </swiper>
        <view class="dot">{{ current }}/3</view>
    </view>
</template>
<script>
export default {
    data() {
        return {
            background: ["red", "gold", "green"],
            autoplay: true,
            interval: 2000,
            duration: 500,
            current: 0
        };
    },
    methods: {
        handleChange(event) {
            const { detail } = event;
            this.current = detail.current;
        }
    }
};
</script>
<style lang="scss" scoped>
.details-swiper-box {
    position: relative;
    .details-swiper {
        width: 100%;
        height: 720rpx;
        .swiper-item {
            width: 100%;
            height: 100%;
        }
    }
    .dot {
        position: absolute;
        bottom: 30rpx;
        right: 30rpx;
        background: #000000;
        border-radius: 30rpx;
        opacity: 0.4;
        padding: 8rpx 14rpx;
        box-sizing: border-box;
        font-size: 22rpx;
        color: #ffffff;
    }
    .red {
        background-color: red;
    }
    .green {
        background-color: green;
    }
    .gold {
        background-color: gold;
    }
}
</style>
