<template>
    <view class="product-list flex flex-wrap" :class="['product-list-' + mode]">
        <view class="product-item" v-for="(item, index) in 20" :key="index">
            <view class="product-item-box">
                <view class="product-item-top__box flex-ajcenter">
                    <image lazy-load src="https://test.oss.zoomspace.vip/images/202010/goods_img/0_G_1602894801192.jpg" class="full-width-height" mode="aspectFill"></image>
                </view>
                <view class="product-item-bottom__box">
                    <view class="product-item-bottom__title ss-ellipsis-2"><text>热销</text>家居健康大礼包1舒适五件套(过滤器、除菌机)</view>
                    <view class="product-item-bottom__price mt12">
                        <text>¥</text>1980.00
                    </view>
                    <view class="product-item-bottom__num">177人已购买</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        props: {
            mode: {
                type: String,
                default: 'column'
            }
        }
    }
</script>

<style lang="scss" scoped>
    .product-list{
        border-radius: 24rpx 24rpx 0px 0px;
        background-color: $bgColor;
        padding: 12rpx;
        &.product-list-row{
           .product-item{
               width: 100%;
               .product-item-box{
                   display: flex;
                   padding: 24rpx;
                   .product-item-top__box{
                       width: 200rpx;
                       height: 200rpx;
                       image{
                           border-radius: 0;
                       }
                   }
                   .product-item-bottom__box{
                       flex: 1;
                       padding: 0;
                       padding-left: 24rpx;
                   }
               }
           }
        }
        .product-item-top__box{
            width: 100%;
            height: 340rpx;
            overflow: hidden;
            image{
                border-radius: 16rpx 16rpx 0 0;
            }
        }
        .product-item-bottom__box{
            padding: 16rpx 16rpx 30rpx;
            line-height: 40rpx;
            .product-item-bottom__num{
                font-size: 20rpx;
                margin-top: 8rpx;
                font-family: PingFangSC-Regular, PingFang SC;
                font-weight: 400;
                color: #999999;
                line-height: 28rpx;
            }
            .product-item-bottom__price{
                line-height: 40rpx;
                font-family: D-DIN-Bold, D-DIN;
                font-weight: bold;
                color: #FA3F1E;
                font-size: 40rpx;
                text{
                    font-size: 24rpx;
                }
            }
            .product-item-bottom__title{
                font-size: 28rpx;
                height: 80rpx;
                font-family: PingFangSC-Regular, PingFang SC;
                font-weight: 400;
                color: #333333;
                text{
                    margin-right: 6rpx;
                    font-size: 22rpx;
                    font-family: PingFangSC-Regular, PingFang SC;
                    font-weight: 400;
                    color: #FFFFFF;
                    line-height: 30rpx;
                    padding: 0 10rpx;
                    background: linear-gradient(90deg, #EB3819 0%, #F0590B 100%);
                    border-radius: 4rpx;
                }
            }
        }
        .product-item{
            width: 50%;
            padding: 12rpx;
        }
        .product-item-box{
            background: #FFFFFF;
            border-radius: 16rpx;
            height: 100%;
        }
    }
</style>
