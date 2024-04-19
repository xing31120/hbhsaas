import { _filter_const_map } from './index.js'

export const constFilter = (value, key) => {
    _validateDefined(key)
    for (let k in _filter_const_map[key]) {
        if (_filter_const_map[key][k].value == value) return _filter_const_map[key][k].label
    }
}
/**
 * 验证配置项是否存在
 * @param key 配置项键名
 * @private
 */
const _validateDefined = key => {
    if (!_filter_const_map.hasOwnProperty(key)) {
        throw new Error(`constant dosen't find ${key}`)
    }
    return _filter_const_map[key]
}

/**
 * 获取配置项
 * @param key 配置项键名
 */
export const getDefined = key => {
    return _validateDefined(key)
}

/**
 * 获取配置项深拷贝(在实例修改不影响原常量值)
 * @param key 配置项键名
 */
export const getDefinedCopy = key => {
    return JSON.parse(JSON.stringify(_validateDefined(key)))
}

/**
 * 获取配置项中的某个值 （只对数组或对象有效）
 * @param key 配置项键名
 * @param value 值
 * @param by 值对应的匹配方式
 * @returns {{}} 某个值
 */
export const getDefinedValue = (key, value, by = 'value') => {
    let obj = _validateDefined(key)
    let val = {}

    if (typeof obj != 'object') {
        return val
    }

    for (var _key of Object.keys(obj)) {
        if (obj[_key][by] == value) {
            return obj[_key]
        }
    }

    return val
}

/**
 * 输入key 值，将CONSTMAP中的key对应的Object按照value大小从小到达排序
 * @param {*} key
 */
export const constMapToArray = key => {
    _validateDefined(key)
    let value = _filter_const_map[key]
    if (!_.isPlainObject(value)) {
        if (_.isArray(value)) return value
        else throw new Error(`${value} must be Object or Array`)
    } else {
        let arr = []
        for (let k in value) {
            arr.push(value[k])
        }
        arr.sort((pre, next) => {
            return pre.value - next.vaue
        })
        return arr
    }
}
