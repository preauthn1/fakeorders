<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

Route::group('/wallet/v1', function () {
    // 获取语言包
    Route::get('/lang_list', [app\controller\v1\IndexController::class, 'lang_list']);
    // 翻译
    Route::get('/translate', [app\controller\v1\IndexController::class, 'translate']);
    //获取恒生指数K线数据
    Route::get('/hsi_list', [app\controller\v1\IndexController::class, 'hsi_list']);
    //获取虚拟币K线数据
    Route::get('/virtual_chart', [app\controller\v1\IndexController::class, 'virtual_chart']);
    // 添加未翻译的语言条目
    Route::get('/add_lang', [app\controller\v1\IndexController::class, 'add_lang']);
    // 获取配置
    Route::get('/config', [app\controller\v1\IndexController::class, 'config']);
    // 测试
    Route::any('/test', [app\controller\v1\IndexController::class, 'test']);
    // 首页
    Route::any('/index', [app\controller\v1\IndexController::class, 'index']);
    // 上传文件
    Route::any('/upload', [app\controller\v1\IndexController::class, 'upload']);
    // 获取内容（公告 文章 ）
    Route::any('/content', [app\controller\v1\IndexController::class, 'content']);
    Route::group('/payment', function () {
        // 充值回调接口
        Route::any('/pay_notify', [app\controller\v1\PaymentController::class, 'pay_notify']);
        // 提现回调接口
        Route::any('/withdraw_notify', [app\controller\v1\PaymentController::class, 'withdraw_notify']);
    });
    Route::group('/payment', function () {
        //申请提现
        Route::post('/add_withdraw', [app\controller\v1\PaymentController::class, 'add_withdraw']);
        //充值订单
        Route::post('/add_recharge', [app\controller\v1\PaymentController::class, 'add_recharge']);
        //获取提现地址
        Route::get('/withdraw_address', [app\controller\v1\PaymentController::class, 'withdraw_address']);
        //获取提现记录
        Route::get('/withdraw_record', [app\controller\v1\PaymentController::class, 'withdraw_record']);
    })->middleware([
        \app\middleware\TokenMiddleware::class
    ]);;
    // 用户接口
    Route::group('/user', function () {
        // 登录
        Route::post('/login', [app\controller\v1\UserController::class, 'login']);
        // 注册
        Route::post('/register', [app\controller\v1\UserController::class, 'register']);
        // 发送邮箱验证码
        Route::post('/send_email', [app\controller\v1\UserController::class, 'send_email']);
        //校验邮箱验证码
        Route::post('/verify_email_code', [app\controller\v1\UserController::class, 'verify_email_code']);
        //重置密码
        Route::post('/reset_password', [app\controller\v1\UserController::class, 'reset_password']);
    });
    // 用户接口
    Route::group('/user', function () {
        // 用户信息
        Route::get('/info', [app\controller\v1\UserController::class, 'info']);
        // 设置语言
        Route::post('/set_lang', [app\controller\v1\UserController::class, 'set_lang']);
        // 获取KYC
        Route::get('/get_kyc', [app\controller\v1\UserController::class, 'get_kyc']);
        // 上传KYC
        Route::post('/upload_kyc', [app\controller\v1\UserController::class, 'upload_kyc']);
        // 绑定钱包
        Route::post('/bind_wallet', [app\controller\v1\UserController::class, 'bind_wallet']);
        // 用户钱包列表
        Route::get('/wallet_list', [app\controller\v1\UserController::class, 'wallet_list']);
        // 获取账单
        Route::get('/bill_list', [app\controller\v1\UserController::class, 'bill_list']);
        // 获取返佣团队
        Route::get('/rebates_team', [app\controller\v1\UserController::class, 'rebates_team']);
        // 获取返佣团队列表
        Route::get('/rebates_team_list', [app\controller\v1\UserController::class, 'rebates_team_list']);
    })->middleware([
        \app\middleware\TokenMiddleware::class
    ]);
    // 消息接口
    Route::group('/message', function () {
        // 获取用户消息
        Route::get('/get_list', [app\controller\v1\MessageController::class, 'get_list']);
        //已读消息
        Route::get('/read', [app\controller\v1\MessageController::class, 'read']);
    })->middleware([\app\middleware\TokenMiddleware::class]);

    // 活期/长期质押
    Route::group('/pledge', function () {
        //获取长期质押配置
        Route::get('/pledge_long_config', [app\controller\v1\PledgeController::class, 'pledge_long_config']);
        // 添加长期质押
        Route::post('/add_pledge_long', [app\controller\v1\PledgeController::class, 'add_pledge_long']);
        // 获取长期质押列表
        Route::get('/pledge_long_list', [app\controller\v1\PledgeController::class, 'pledge_long_list']);
        // 获取长期质押图表数据
        Route::get('/pledge_long_chart', [app\controller\v1\PledgeController::class, 'pledge_long_chart']);

    })->middleware([\app\middleware\TokenMiddleware::class]);
    //视频任务
    Route::group('/video', function () {
        // 获取视频列表
        Route::get('/get_video_list', [app\controller\v1\VideoController::class, 'get_video_list']);
        // 获取视频
        Route::get('/get_video', [app\controller\v1\VideoController::class, 'get_video']);
        // 添加视频任务
        Route::post('/add_task', [app\controller\v1\VideoController::class, 'add_task']);
        // 获取任务列表
        Route::get('/get_task_list', [app\controller\v1\VideoController::class, 'get_task_list']);
        // 更新任务图片
        Route::post('/update_img', [app\controller\v1\VideoController::class, 'update_img']);
    })->middleware([\app\middleware\TokenMiddleware::class]);
});

