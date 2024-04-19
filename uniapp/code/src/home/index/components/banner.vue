<template>
    <view class="swiperBox">
        <swiper class="swiper" :autoplay="autoplay" :interval="interval" :duration="duration" :style="{height: height}" :current="active" @change="change">
            <swiper-item v-for="(item,index) in list" :key="index">
                <view class="swiper-item" @tap="jump(item.path)">
                    <image :src="item.src" mode="aspectFill" />
                </view>
            </swiper-item>
        </swiper>
        <view class="origin">
            <view class="item" v-for="(item,index) in list" :key="index" :class="{active: active===index}"></view>
        </view>
    </view>
</template>
<script>
export default {
    props:{
        height: {
            type: String,
            default: '280rpx'
        },
        autoplay:{
            type: Boolean,
            default: true
        },
        interval:{
            type: Number,
            default: 5000
        },
        duration:{
            type: Number,
            default: 300
        },
        list: {
            type: Array,
            defaut(){
                return []
            }
        }
    },
    data() {
        return {
            active: 0
        }
    },
    methods: {
        jump(url){
            if (!url) return
            uni.navigateTo({
                 url
            })
        },
        change(enent){
            this.active = enent.detail.current
        }
    }
}
</script>
<style lang="scss" scoped>
.swiperBox{
    position: relative;
    .swiper{
        width: 100%;
        .swiper-item{
            width: 100%;height: 100%;background: #fff;
            image{
                width: 100%;height: 100%;
            }
        }
    }
    .origin{
        position: absolute;bottom: 7rpx;left: 50%;transform: translateX(-50%);height: 10rpx;white-space: nowrap;font-size: 0;
        .item{
            width: 10rpx;height: 10rpx;border-radius: 5rpx;transition: all .3s;background: #fff;opacity: .5;display: inline-block;margin: 0 6rpx;
        }
        .item.active{
            opacity: 1;background-color: #FA3F1E;
        }
    }
}
</style>
