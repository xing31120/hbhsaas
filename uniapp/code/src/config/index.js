/** *
 * 常量配置
 * @env '运行环境'
 * @hostUrl '请求地址'
 * @ossImgUrl 'oss图片地址'
 * @version· '微信审核版本号'
 */

// node环境变量
const env = process.env.NODE_ENV

// 请求服务器接口配置地址
const hostUrl = process.env.NODE_ENV == 'production' ? "https://shop.meijiabang.com" : "https://shop2.meijiabang.com"

// oss图片配置地址
const ossImgUrl = 'https://static.oss.meijiabang.com/zzyx/'

// 微信审核版本号，审核时需与后端api config接口的 *_wxapp_version 字段统一，审核完成后由后端修改
// 当前版本号 1
const version = 1

export { env, hostUrl, ossImgUrl, version }