<template>
    <view>
        <view class="goods-price">
            <view class="flex align-center justify-between ">
                <view class="num">1699<text class="sub">¥1999</text></view>
                <view class="share flex"
                    >分享赚<view class="share-num flex-ajcenter"
                        >16%</view
                    ></view
                >
            </view>
            <view class="flex align-center">
                <view class="tag">分享赚</view>
                <text class="goods-price__title"
                    >舒适全屋杀毒消菌全氧加湿器</text
                >
            </view>
            <view class="goods-price__sales flex justify-between">
                <view>销量 2323</view>
                <view>库存 53344</view>
            </view>
        </view>

        <view class="goods-specs">
            <view class="goods-specs-item">
                <text class="goods-specs__title">已选</text>
                <view class="goods-specs__content ss-underline">
                    <text class="goods-specs__text">12322</text>
                    <text>right</text>
                </view>
            </view>
            <view class="goods-specs-item">
                <text class="goods-specs__title">已选</text>
                <view class="goods-specs__content ss-underline">
                    <text class="goods-specs__text">12322</text>
                    <text>right</text>
                </view>
            </view>
            <view class="goods-specs-item">
                <text class="goods-specs__title">已选</text>
                <view class="goods-specs__content ">
                    <text class="goods-specs__text">12322</text>
                </view>
            </view>
        </view>
    </view>
</template>
<style lang="scss" scoped>
.goods-price {
    padding: 24rpx;
    box-sizing: border-box;
    background-color: #fff;
    margin-bottom: 24rpx;
    .num {
        font-size: 56rpx;
        font-weight: bold;
        color: #fa3f1e;
        position: relative;
        padding-left: 16rpx;
        &::before {
            content: "¥";
            position: absolute;
            bottom: 10rpx;
            left: 0;
            font-size: 28rpx;
        }
        .sub {
            font-size: 28rpx;
            font-weight: normal;
            color: #999999;
            margin-left: 16rpx;
        }
    }
    .share {
        align-items: center;
        justify-content: space-between;
        padding: 0 4rpx 0 10rpx;
        background: #fa3f1e;
        border-radius: 30rpx;
        font-size: 24rpx;
        color: #ffffff;
        // width: 166rpx;
        height: 60rpx;
        background: #fa3f1e;
        border-radius: 30rpx;
        width: fit-content;
        .share-num {
            height: 100%;
            // width: fit-content;
            background: rgba(255, 255, 255, 0.9);
            padding: 0 10rpx;
            margin-left: 10rpx;
            border-radius: 26rpx;
            // width: 70rpx;
            height: 52rpx;

            font-size: 24rpx;
            color: #fa3f1e;
        }
    }

    .tag {
        padding: 2rpx 8rpx;
        // height: 32rpx;
        box-sizing: border-box;
        background: linear-gradient(90deg, #eb3819 0%, #f0590b 100%);
        border-radius: 4rpx;
        font-size: 20rpx;
        color: #ffffff;
        margin-right: 16rpx;
    }
    .goods-price__title {
        font-size: 32rpx;
        font-weight: 500;
        color: #333333;
    }
    .goods-price__sales {
        margin-top: 16rpx;
        font-size: 22rpx;
        color: #999999;
    }
}
.goods-specs {
    display: flex;
    flex-direction: column;
    .goods-specs-item {
        background-color: #fff;
        width: 100%;
        height: 88rpx;
        display: flex;
        align-items: center;
        .goods-specs__title {
            padding-left: 32rpx;
            width: 132rpx;
            font-size: 26rpx;
            font-weight: 500;
            color: #333333;
        }
        .goods-specs__content{
            width: 100%;
            height: 100%;
            padding-right: 24rpx;
            display: flex;
            align-items: center;
            .goods-specs__text{
                flex: 1;
            }
        }
    }
}
</style>
