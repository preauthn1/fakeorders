window.rootPath = (function (src) {
    src = document.currentScript
        ? document.currentScript.src
        : document.scripts[document.scripts.length - 1].src;
    return src.substring(0, src.lastIndexOf("/") + 1);
})();

layui.config({
    base: rootPath + "module/",
    version: "3.11.4"
}).extend({
    admin: "admin", 	// 框架布局组件
    menu: "menu",		// 数据菜单组件
    frame: "frame", 	// 内容页面组件
    tab: "tab",			// 多选项卡组件
    echarts: "echarts", // 数据图表组件
    echartsTheme: "echartsTheme", // 数据图表主题
    encrypt: "encrypt",		// 数据加密组件
    select: "select",	// 下拉多选组件
    xmSelect: "xm-select",	// 下拉多选组件 //变更
    drawer: "drawer",	// 抽屉弹层组件
    notice: "notice",	// 消息提示组件
    step: "step",		// 分布表单组件
    tag: "tag",			// 多标签页组件
    popup: "popup",      // 弹层封装
    treetable: "treetable",   // 树状表格
    dtree: "dtree",			// 树结构
    tinymce: "tinymce/tinymce", // 编辑器
    area: "area",			// 省市级联
    count: "count",			// 数字滚动
    topBar: "topBar",		// 置顶组件
    button: "button",		// 加载按钮
    design: "design",		// 表单设计
    card: "card",			// 数据卡片组件
    loading: "loading",		// 加载组件
    cropper: "cropper",		// 裁剪组件
    convert: "convert",		// 数据转换
    yaml: "yaml",			// yaml 解析组件
    context: "context",		// 上下文组件
    http: "http",			// ajax请求组件
    theme: "theme",			// 主题转换
    message: "message",     // 通知组件
    toast: "toast",         // 消息通知
    iconPicker: "iconPicker",// 图标选择
    nprogress: "nprogress",  // 进度过渡
    watermark: "watermark/watermark", //水印
    fullscreen: "fullscreen",  //全屏组件
    popover: "popover/popover" //汽泡组件
}).use(['layer', 'theme', 'jquery'], function () {
    layui.theme.changeTheme(window, false);
    var $ = layui.$;
    let form = layui.form;
    let languagePacks = {};

    /**
     * 添加条目
     * @param key
     */
    function addLang(key) {
        var lang = localStorage.getItem(key);
        if (!lang) {
            $.ajax({
                url: "/app/admin/index/add_lang?title=" + key,
                dataType: 'json',
                success: function (res) {
                    localStorage.setItem(key, "1");
                }
            });
        }
    }

    window.addLang = addLang;

// 多语言文本获取函数
    // 多语言文本获取函数（优化版）
    function getText(key, params) {
        // 获取当前语言设置（默认中文）
        var currentLang = localStorage.getItem('admin_language') || 'zh-CN';

        // 如果当前语言包不存在，使用默认语言
        if (languagePacks) {
            if (!languagePacks[currentLang]) {
                currentLang = 'zh-CN';
            }

            var text = key;
            // 如果key在语言包中存在，则使用翻译文本，否则使用key本身
            if (languagePacks[currentLang][key]) {
                text = languagePacks[currentLang][key];
            } else {
                addLang(key);
            }

            // 如果提供了参数，则进行模板替换
            if (params && typeof params === 'object') {
                for (var param in params) {
                    if (params.hasOwnProperty(param)) {
                        text = text.replace(new RegExp('\\{' + param + '\\}', 'g'), params[param]);
                    }
                }
            }

            return text;
        } else {
            // 如果语言包未加载，直接返回key，并进行参数替换（如果需要）
            var result = key;
            if (params && typeof params === 'object') {
                for (var param in params) {
                    if (params.hasOwnProperty(param)) {
                        result = result.replace(new RegExp('\\{' + param + '\\}', 'g'), params[param]);
                    }
                }
            }
            return result;
        }
    }

    // 将 getText 函数设置为全局方法
    window.getText = getText;

//初始化语言包
    function initLanguage() {
        languagePacks = JSON.parse(localStorage.getItem("admin_language_packs"));
        if (!languagePacks) {
            languagePacks = languagePacks || {
                'zh-CN': {},
                'en': {},
            };
        }
        // 重新应用语言
        $('[data-lang]').each(function () {
            var langKey = $(this).data('lang');
            var text = getText(langKey);
            if (this.tagName === 'INPUT' || this.tagName === 'TEXTAREA') {
                if ($(this).attr('type') === 'radio') {
                    $(this).attr('title', text);
                } else if ($(this).attr('type') === 'checkbox') {
                    $(this).attr('lay-text', text);
                    $(this).attr('title', text);
                } else {
                    $(this).attr('placeholder', text);
                }
            } else {
                $(this).text(text);
            }
        });
        // 重新渲染表单以应用更改
        if (typeof layui !== 'undefined' && layui.form) {
            layui.form.render();
        }
    }

    initLanguage();
});