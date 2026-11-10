// 一些常用的方法
export function aa(){
  console.log(';11111');
}

// 千分位
export function ThousandAndDecimal(num) {
  return num.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,')
}


// 数字转为千分位，并保留两个小数位
export function numberFilter(num, type) {
	let res = num - 0
	let obj = false
	if (res - Math.floor(res) == 0) {
		obj = true
	}
	if (num && num !== null) {
		res = num.toString().replace(/\d+/, (n) => {
			return n.replace(/(\d)(?=(\d{3})+$)/g, ($1) => {
				return $1 + ','
			})
		})
	}
	if (obj && !type) {
		res += '.00'
	}
	return res
}

/**
 * 数字转整数 如 100000 转为10万,保留整数
 * 不满10万时，换算为元，页面不展示单位
 * @param {需要转化的数} num
 */

export function tranNumber(num) {
  var numStr = num.toString()
  if (numStr.length <= 6) {
    return parseFloat(num)
  }
  if (numStr.length > 6) {
    var decimal = numStr.substring(
      numStr.length - 4,
      numStr.length - 4
    )
    return parseInt(num / 10000) + decimal + '万'
  }
}
// console.log(tranNumber(577832.2)) //57万


/** 金额保留两位小数 */
export function toMoneyNum(num) {
	return Number(num).toFixed(2)
}

// 阿拉伯数字转中文数字
export const num2cn = (num) => {
	var AA = Array.from([
		'零',
		'一',
		'二',
		'三',
		'四',
		'五',
		'六',
		'七',
		'八',
		'九',
	])
	var BB = Array.from(['', '十', '百', '千', '万', '亿', '点', ''])
	var a = ('' + num).replace(/(^0*)/g, '').split('.')
	var k = 0
	var re = ''
	for (var i = a[0].length - 1; i >= 0; i--) {
		switch (k) {
			case 0:
				re = BB[7] + re
				break
			case 4:
				if (
					!new RegExp('0{4}\\d{' + (a[0].length - i - 1) + '}$').test(
						a[0]
					)
				) {
					re = BB[4] + re
				}
				break
			case 8:
				re = BB[5] + re
				BB[7] = BB[5]
				k = 0
				break
		}
		if (k % 4 === 2 && a[0].charAt(i + 2) !== 0 && a[0].charAt(i + 1) === 0)
			re = AA[0] + re
		if (a[0].charAt(i) !== 0) re = AA[a[0].charAt(i)] + BB[k % 4] + re
		k++
	}
	if (a.length > 1) {
		re += BB[6]
		for (let i1 = 0; i1 < a[1].length; i1++) re += AA[a[1].charAt(i1)]
	}
	return re
}

// 格式化倒计时，秒为单位 返回形式 hh:mm:ss :为分隔符
export const formatCountDown = (
	time,
	hoursplit = '',
	minutesplit = '',
	secondsplit = ''
) => {
	const hours =
		moment.duration(time, 'seconds').hours() > 0
			? moment.duration(time, 'seconds').hours()
			: ''
	const minutes =
		moment.duration(time, 'seconds').minutes() > 0
			? moment.duration(time, 'seconds').minutes()
			: ''
	const seconds =
		moment.duration(time, 'seconds').seconds() > 0
			? moment.duration(time, 'seconds').seconds()
			: 0
	// secondsplit = (hours && seconds) ? secondsplit : 's'
	return formatTime(minutes, minutesplit) + formatTime(seconds, secondsplit)
}


// 单独拿到页面里面去用
function doHandleMonth(month) {
  var m = month
  if (month.toString().length == 1) {
    m = '0' + month
  }
  return m
}

function getDay(day) {
  var today = new Date()

  var targetday_milliseconds = today.getTime() + 1000 * 60 * 60 * 24 * day

  today.setTime(targetday_milliseconds) //注意，这行是关键代码

  var tYear = today.getFullYear()
  var tMonth = today.getMonth()
  var tDate = today.getDate()
  tMonth = doHandleMonth(tMonth + 1)
  tDate = doHandleMonth(tDate)
  return tYear + '-' + tMonth + '-' + tDate
}

// console.log(getDay(0),'----sss  2023-02-03');


// 格式化时间
export const formatTime = (diffTime, split) => {
	const time = diffTime || 0
	return time > 9 ? time + '' + split : '0' + time + split
}


// 防抖
export function myDebounce(fn, delayTime) {
	let timer = null
	return function () {
		if (timer) clearTimeout(timer)
		const _this = this
		const _arguments = arguments
		timer = setTimeout(() => {
			fn.apply(_this, _arguments)
		}, delayTime)
	}
}

// 节流
export function myThrottle(fn, delayTime) {
	let timer = null
	return function () {
		if (timer) return
		const _this = this
		const _arguments = arguments
		fn.apply(_this, _arguments)
		timer = setTimeout(() => {
			clearTimeout(timer)
			timer = null
		}, delayTime)
	}
}

// 深拷贝
export function deepClone(target) {
	return JSON.parse(JSON.stringify(target))
	// return _.cloneDeep(target)
}

// 获取当前时间
export function getNowTime() {
	var timestamp = new Date().getTime()
	var date = new Date(timestamp)
	var Y = date.getFullYear()
	var M =
		date.getMonth() + 1 < 10
			? '0' + (date.getMonth() + 1)
			: date.getMonth() + 1
	var D = date.getDate()
	var h = date.getHours()
	var m = date.getMinutes()
	var s = date.getSeconds()
	// console.log(Y+M+D+h+m+s);
	return Y + M + D + h + m + s
}

/** 手机号码校验 */
export const isMobile = (str) => {
	const checkstr = /^1[0-9]{10}$/
	return checkstr.test(str)
}
