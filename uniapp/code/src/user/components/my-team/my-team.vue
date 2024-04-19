<template>
    <view class="contain">
        <view class="team">
            <view class="my-team">
                <text class="uname">我的团队</text>
                <!-- <text class="flexc">查看全部<uni-icons color="#333" type="eye"/></text> -->
            </view>
            <view class="team-info">
                <view class="my-account">
                    <image class="avater" src="/static/logo.png"></image>
                    <view class="user-info">
                        <view class="uname">
                            <text>王大大的大大</text>
                            <view class="copy">复制微信</view>
                        </view>
                        <view class="desc"
                            >为你的专属导师，赶紧和导师“约”起来吧！</view
                        >
                    </view>
                </view>
                <view class="info">
                    <view class="info-item">
                        <text class="num">54</text>
                        <text>我的团队</text>
                    </view>
                    <view class="info-item">
                        <text class="num">54</text>
                        <text>我的团队</text>
                    </view>
                    <view class="info-item">
                        <text class="num">54</text>
                        <text>我的团队</text>
                    </view>
                </view>
                <view class="invitation-btn">
                    邀请好友
                </view>
            </view>
        </view>
        <!-- 我的XX -->
        <view class="team">
            <view class="my-team">
                <text class="uname">我的XX</text>
                <!-- <text class="flexc">查看全部<uni-icons color="#333" type="eye"/></text> -->
            </view>
            <view class="team-state">
                <view class="team-item">
                    <uni-icons color="#333" size="60" type="eye" />
                    <text>待付款</text>
                </view>
                <view class="team-item">
                    <uni-icons color="#333" size="60" type="eye" />
                    <text>待付款</text>
                </view>
            </view>
        </view>
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
.contain {
    padding: 0 20rpx;
    box-sizing: bteam-box;
    margin-bottom: 24rpx;
    .team {
        width: 100%;
        background: #ffffff;
        border-radius: 16rpx;
        padding: 0 28rpx;
        box-sizing: bteam-box;
        &:first-child {
            margin-bottom: 24rpx;
        }
        .my-team {
            height: 90rpx;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1rpx solid #ededed;
            .uname {
                font-size: 32rpx;
                font-weight: 500;
                color: #000000;
            }
        }
        .team-state {
            display: flex;
            flex-wrap: wrap;
            .team-item {
                width: 25%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 32rpx 0;
                color: #333333;
                position: relative;
                .num {
                    font-size: 36rpx;
                    font-weight: bold;
                    color: #000;
                    margin-bottom: 16rpx;
                }
            }
        }
    }
    
    .team-info {
        padding-bottom: 40rpx;
        .my-account {
            display: flex;
            padding: 24rpx 0 32rpx;
            .avater {
                width: 80rpx;
                height: 80rpx;
                margin-right: 24rpx;
                border-radius: 50%;
            }
            .user-info {
                display: flex;
                flex-direction: column;
                // justify-content: center;
                flex: 1;
                .uname {
                    display: flex;
                    justify-content: space-between;
                    font-weight: 500;
                    color: #000;
                    margin-bottom: 8rpx;
                    font-size: 28rpx;
                    .copy {
                        background: #09cf67;
                        border-radius: 4rpx;
                        border: 1rpx solid #04a954;
                        font-size: 20rpx;
                        padding: 6rpx 16rpx;
                        box-sizing: border-box;
                        color: #fff;
                    }
                }
                .desc {
                    font-size: 24rpx;
                    color: #666666;
                }
            }
        }
        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24rpx;
            .info-item {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                width: 202rpx;
                // height: 157px;
                background: #fffbf7;
                border-radius: 8rpx;
                padding: 32rpx 0;
            }
        }
        .invitation-btn {
            width: 100%;
            height: 72rpx;
            line-height: 72rpx;
            text-align: center;
            background: linear-gradient(90deg, #ffe4c0 0%, #ffc88e 100%);
            border-radius: 8rpx;
            font-size: 28rpx;
            color: #ab6127;
        }
    }
}
</style>
