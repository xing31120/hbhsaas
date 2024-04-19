import Vue from 'vue'
import { Store } from 'vuex'
declare module 'vue/types/vue' {
// 声明为 Vue 补充的东西
  interface Vue {
    $http: Function;
	$store: Store<any>;
	$config: any;
	$utilFn: any;
	$toast: Function;
	$loading: Function;
	$hideLoading: Function;
	$modal: Function;
	$deepClone: Function;
	$typeOf: Function;
	$session: any;
  }
}