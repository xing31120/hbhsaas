<template>
    <view class="ss-content product-search-content">
        <view class="search-top-fixed-clear">
            <view class="search-top-fixed">
                <view class="search-box flex-ajcenter">
                    <global-ss-input class="search-box__input flex-1" placeholder="输入搜索关键词">
                        <view class="search-box__before" slot="before">
                            <image class="search-box__before_image" src="~@/static/image/product/search.png" mode="widthFix"></image>
                        </view>
                    </global-ss-input>
                    <view class="search-box__after" @click="changeMode">
                        <image class="search-box__after_image" v-if="listMode === 'column'" src="../../static/image/product/column-icon.png" mode="widthFix"></image>
                        <image class="search-box__after_image" v-else src="../../static/image/product/row-icon.png" mode="widthFix"></image>
                    </view>
                </view>
                <view class="filter-box flex-ajcenter tc">
                    <view class="flex-1">
                        综合推荐
                    </view>
                    <view class="flex-1">
                        品牌
                    </view>
                    <view class="flex-1">
                        销量
                    </view>
                    <view class="flex-1">
                        价格
                    </view>
                    <view class="flex-1">
                        筛选
                    </view>
                </view>
            </view>
            <view class="filter-content">
                <view class="filter-item-box">
                    <view class="filter-item" v-for="(item, index) in 9" :key="index" @click="changeFilter('brand', index)">
                        <image src="../../static/image/product/filter-select.png"></image>
                        <text>美的{{index + 1}}</text>
                    </view>
                </view>
            </view>
        </view>
        <view class="product-list-box">
            <product-list :mode="listMode" />
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                listMode: 'row',
                brand: []
            }
        },
        computed: {
            selectItem_() {
                
            }
        },
        methods: {
            /**
             *  pro 属性名
             * index 当前选中的下标
             * isMultiple 是否多选
             */
            changeFilter(pro, index, isMultiple = true) {
                let isExitIndex = this[pro].indexOf(index);
                if(isExitIndex !== -1){
                    this[pro].splice(isExitIndex, 1);
                }else{
                    if(isMultiple){
                        this[pro].push(index);
                    }else{
                        this[pro][0] = index;
                    }
                }
            },
            changeMode() {
                this.listMode = this.listMode === 'column' ? 'row' : 'column';
            }
        }
    }
</script>

<style lang="scss">
    .product-search-content{
        .search-top-fixed-clear{
            height: 176rpx;
            background-color: #fff;
            .filter-content{
                @include fixed(var(--window-top), 0, 0, 0);
                background: rgba(#000, .6);
                z-index: 99;
                padding-top: 176rpx;
                .filter-item-box{
                    background-color: #fff;
                    font-size: 0;
                    padding: 12rpx;
                    .filter-item{
                        padding: 12rpx;
                        display: inline-block;
                        font-size: 28rpx;
                        color: #333333;
                        min-width: 33.33333333%;
                        line-height: 40rpx;
                        position: relative;
                        @include tst();
                        image{
                            @include abs(50%, null, null, 0);
                            width: 30rpx;
                            height: 30rpx;
                            transform: translateY(-50%);
                            opacity: 0;
                            @include tst();
                        }
                        &.filter-item__active{
                            font-size: bold;
                            color: #FA3F1E;
                            padding-left: 36rpx;
                            image{
                                opacity: 1;
                            }
                        }
                    }
                }
            }
            .search-top-fixed{
                background-color: inherit;
                @include fixed(var(--window-top), 0);
                width: 100%;
                z-index: 100;
                .filter-box{
                    height: 80rpx;
                }
            }
        }
        .product-list-box{
            background-color: #fff;
        }
        .search-box{
            background-color: #fff;
            padding: 8rpx 18rpx 24rpx 28rpx;
            .search-box__input{
                background-color: #F4F5F6;
                border-radius: 32px;
            }
            .search-box__before{
                padding-right: 16rpx;
            }
            .search-box__after{
                padding-left: 38rpx;
            }
            .search-box__after_image{
                width: 48rpx;
                height: 48rpx;
                display: block;
            }
            .search-box__before_image{
                width: 32rpx;
                height: 32rpx;
                display: block;
            }
        }
    }
</style>
