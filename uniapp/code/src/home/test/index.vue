<template>
    <view style="background-color: red;">
        <ss-menu-scroll mode="homeIndex" scrollX="true" :config="menuList" @change="changeMenu" />
    </view>
</template>

<script>
    export default {
        data() {
            return {
                menuList: [{
                    label: '品类1',
                    value: '1'
                }, {
                    label: '品类2',
                    value: '1'
                }, {
                    label: '品类3',
                    value: '1'
                }, {
                    label: '品类4',
                    value: '1'
                }, {
                    label: '品类5',
                    value: '1'
                }, {
                    label: '品类6',
                    value: '1'
                }]
            }
        },
        methods: {
            changeMenu(e) {
                console.log(e);
            }
        }
    }
</script>

<style>
</style>
