// 姓名
const nameReg = /^[\u4E00-\u9FA5A-Za-z]{0,20}$/
// 手机号
// const mobileReg = /^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/
const mobileReg = /^(1[3-9])\d{9}$/
// 账号
const accountReg = /^[A-Za-z0-9_]{4,10}$/
// 密码
const pwdReg = /^[A-Za-z0-9]{6,}$/
// 面积
const areaReg = /^[0-9]{2,4}$/

export { nameReg, mobileReg, accountReg, pwdReg, areaReg }