<template>
    <view class="search" :class="{top: isTop}">
        <view class="headPortrait">
            <image :src="headerImg" mode="aspectFill" />
            <view class="name">
                <text>{{title}}</text>
            </view>
        </view>
        <view class="input" @tap="jump" :class="{fixed: isFixed}" :style="{marginTop: isFixed && capsuleTop + 'px', width: isFixed && '60%'}">
            <image src="@/static/home/search.png" mode="heightFix"/>
            <text>请输入搜索关键词</text>
        </view>
    </view>
</template>
<script>
export default {
    props: {
        path: {
            type: String,
            default: null
        },
        title: {
            type: String,
            default: null
        },
        headerImg: {
            type: String,
            default: null
        },
        scrollTop: {
            type: Number,
            default: 0
        }
    },
    computed: {
        isTop: {
            get(){
                return this.scrollTop <= this.containerTop ? false : true
            }
        },
        isFixed: {
            get(){
                const isTrue = this.scrollTop <= this.containerTop + this.containerHeight ? false : true
                return isTrue
            },
            set(val){
                console.log(val)
                this.$emit('input',val)
            }
        }
    },
    data() {
        return {
            containerTop: 0,
            containerHeight: 0,
            capsuleTop: 0
        }
    },
    methods: {
        jump(){
            if (!this.path) {
                console.log('未传递搜索路径')
                return
            }
            uni.navigateTo({
                url: this.path
            })
        },
        // 获取左移动位置
        getSearchTop() {
            let query = uni.createSelectorQuery()
                // #ifndef MP-ALIPAY
                .in(this)
                // #endif
            // 获取容器的宽度 
            query.select('.headPortrait').boundingClientRect((data) => {
                if (!this.containerTop && data) {
                    this.containerTop = data.top
                    this.containerHeight = data.height
                }
                }).exec()
        }
    },
    mounted() {
        const data = uni.getMenuButtonBoundingClientRect()
        this.capsuleTop = data.top
        this.$nextTick(() => {
            this.getSearchTop()
        })
    }
}
</script>
<style lang="scss" scoped>
.search{
    padding: 0 24rpx;
    .headPortrait{
        transition: all .3s;height: 64rpx;display: flex;overflow: hidden;
        image{
            height: 64rpx;border-radius: 8rpx;width: 64rpx;background: #ccc;
        }
        .name{
            color: #fff;font-size: 36rpx;padding: 0 16rpx;line-height: 64rpx;height: 64rpx;font-weight: 500;
        }
    }
    .input{
        height: 64rpx;border-radius: 32rpx;background: #fff;display: flex;align-items: center;color: #B8B8B8;font-size: 26rpx;margin-top: 24rpx;transition: all .3s;width: 100%;
        image{
            height: 26rpx;margin: 0 16rpx 0 22rpx;
        }
        &.fixed{
            position: fixed;top: 0;left: 24rpx;width: calc(100% - 48rpx);z-index: 999;
        }
    }
    &.top{
        transition: all .3s;
        .headPortrait{
            opacity: 0;
        }
    }
}
</style>
