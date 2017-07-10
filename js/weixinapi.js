
var wxData = {
    isWxJsSDK: false, //是否开启JS-SDK 
    csrf_url: (window.location.href.indexOf('#') == -1 ? window.location.href : window.location.href.substring(0, window.location.href.indexOf('#'))),
    jsticketurl:'http://m.tuandai.com',
    url: "http://m.tuandai.com",
    title: "团贷网",
    desc: "团贷网活动",
    img_url: "http://m.tuandai.com/imgs/sharelogo.png",
    ishideshare: false, //是否隐藏分享按钮
    async: true, //是否启用每次分享都重置分享内容
    debug: false, //是否开启debug模式
    ShareCallBack: function (ex) { },
    HideShareBtnCallBack: function (res) { }, //隐藏分享按钮触发事件
    BeforeShareCall: function (res) { } //分享之前检测
};

//页面加载时
$(function () {
    var wxcode = getUrlParam("code");
    if (wxData.isWxJsSDK) {
        //        $.ajax({
        //            type: "get",
        //            async: false,
        //            url: "/ajaxCross/WXTokenAjax.ashx",
        //            data: { code: wxcode, url: wxData.csrf_url, r: Math.random() },
        //            dataType: "json",
        //            success: function (json) {
        //                WeChatSDKjsonpCallback(json);
        //            },
        //            error: function () {
        //            }
        //        }); 
        $.ajax({
            url: wxData.jsticketurl + "/ajaxCross/WXTokenAjax.ashx",
            //url: wxData.jsticketurl + "/Activity/WXToken.aspx",
            type: "GET",
            dataType: 'jsonp',
            //jsonp: 'imCallback',
            // jsonpCallback: 'jsonp1',
            data: { code: wxcode, url: wxData.csrf_url, r: Math.random() },
            timeout: 8000,
            success: function (json) {
                WeChatSDKjsonpCallback(json);
            }
        });
    } else {
        wx.hideOptionMenu();
    }
});

//获取URL参数
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]); return null; //返回参数值
}
 

//微信SDK初始化配置
function WeChatSDKjsonpCallback(json) { 
    wx.config({
        debug: wxData.debug,
        appId: json.appid,
        timestamp: json.timeStamp,
        nonceStr: json.nonceStr,
        signature: json.signature,
        jsApiList: [
        'checkJsApi',
        'onMenuShareTimeline',
        'onMenuShareAppMessage',
        'onMenuShareQQ',
        'onMenuShareWeibo',
        'hideMenuItems',
        'showMenuItems', 
        'hideOptionMenu',
        'showOptionMenu' 
        ]
    });
}
wx.ready(function () {
    //隐藏显示分享按钮
    if (wxData.ishideshare) {
        wx.hideMenuItems({
            menuList: [
                'menuItem:share:appMessage', // 分享给好友
                'menuItem:share:timeline', // 分享到朋友圈
                'menuItem:share:weibo', //分享到微博
                'menuItem:share:qq', //分享到QQ
                'menuItem:share:QZone', //分享到 QQ 空间
                'menuItem:copyUrl' // 复制链接
                 ],
            success: function (res) {
                wxData.HideShareBtnCallBack(res);
            },
            fail: function (res) {
                // alert(JSON.stringify(res));
            }
        });
    } else {
        wx.showMenuItems({
            menuList: [
                'menuItem:share:appMessage', // 分享给好友
                'menuItem:share:timeline', // 分享到朋友圈
                'menuItem:share:weibo', //分享到微博
                'menuItem:share:qq', //分享到QQ
                'menuItem:share:QZone', //分享到 QQ 空间
                'menuItem:copyUrl' // 复制链接
              ],
            success: function (res) {
                // alert('已显示“阅读模式”，“分享到朋友圈”，“复制链接”等按钮');
            },
            fail: function (res) {
                // alert(JSON.stringify(res));
            }
        });
    }

    //分享到朋友圈
    wx.onMenuShareTimeline({
        title: wxData.title,
        link: wxData.url,
        imgUrl: wxData.img_url,
        trigger: function (res) {
            wxData.BeforeShareCall("wxtimeline");
            if (wxData.async) {
                if (wxData.friend_circle_title != undefined && wxData.friend_circle_title != null && wxData.friend_circle_title != "") {
                    this.title = wxData.friend_circle_title;
                } else {
                    this.title = wxData.title;
                }
                this.link = wxData.url;  
                this.imgUrl = wxData.img_url;
            }
        },
        success: function () {
            wxData.ShareCallBack("success");
        },
        cancel: function () {
            wxData.ShareCallBack("cancel");
        }
    });
    //发送给好友
    wx.onMenuShareAppMessage({
        title: wxData.title,
        desc: wxData.desc,
        link: wxData.url,
        imgUrl: wxData.img_url,
        type: '',
        dataUrl: '',
        trigger: function (res) {
            wxData.BeforeShareCall("wxfriend");
            if (wxData.async) {
                this.title = wxData.title;
                this.desc = wxData.desc;
                this.link = wxData.url;
                this.imgUrl = wxData.img_url;
            }
        },
        success: function () {
            wxData.ShareCallBack("success");
        },
        cancel: function () {
            wxData.ShareCallBack("cancel");
        }
    });
    //分享到QQ
    wx.onMenuShareQQ({
        title: wxData.title,
        desc: wxData.desc,
        link: wxData.url,
        imgUrl: wxData.img_url,
        trigger: function (res) {
            wxData.BeforeShareCall("qq");
            if (wxData.async) {
                this.title = wxData.title;
                this.desc = wxData.desc;
                this.link = wxData.url;
                this.imgUrl = wxData.img_url;
            }
        },
        success: function () {
            wxData.ShareCallBack("success");
        },
        cancel: function () {
            wxData.ShareCallBack("cancel");
        }
    });
    //分享到腾讯微博
    wx.onMenuShareWeibo({
        title: wxData.title,
        desc: wxData.desc,
        link: wxData.url,
        imgUrl: wxData.img_url,
        trigger: function (res) {
            wxData.BeforeShareCall("qqweibo");
            if (wxData.async) {
                this.title = wxData.title;
                this.desc = wxData.desc;
                this.link = wxData.url;
                this.imgUrl = wxData.img_url;
            }
        },
        success: function () {
            wxData.ShareCallBack("success");
        },
        cancel: function () {
            wxData.ShareCallBack("cancel");
        }
    });

    //分享到QQ空间
    wx.onMenuShareQZone({
        title: wxData.title, // 分享标题
        desc: wxData.desc, // 分享描述
        link: wxData.url, // 分享链接
        imgUrl: wxData.img_url, // 分享图标
        trigger: function (res) {
            wxData.BeforeShareCall("qqzone");
            if (wxData.async) {
                this.title = wxData.title;
                this.desc = wxData.desc;
                this.link = wxData.url;
                this.imgUrl = wxData.img_url;
            }
        },
        success: function () {
            wxData.ShareCallBack("success");
        },
        cancel: function () {
            wxData.ShareCallBack("cancel");
        }
    });
});

wx.error(function (res) {
    if (wxData.debug) {
        alert("授权错误:" + res);
    }
}); 

