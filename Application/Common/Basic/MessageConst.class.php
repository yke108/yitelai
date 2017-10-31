<?php
namespace Common\Basic;
class MessageConst{
	const SmsTpOnOrderSend = 1;  //发货通知
	const SmsTpOnOrderPay = 2;  //支付通知
	const SmsTpOnRecharge = 3;  //充值通知
	const SmsTpOnCash = 4;  //提现通知
	const SmsTpOnMerchantApplyPass = 5;  //商家入驻申请通过
	const SmsTpOnDistributorAccount = 6;  //店铺账号密码
	const SmsTpOnDistributorFine = 7;  //罚款
	const SmsTpOnDistributorCash = 8;  //店铺提现通知
	
	const JpushPlatformAll = 1;
	const JpushPlatformAndroid = 2;
	const JpushPlatformIos = 3;
	
	const JPushAppTypeUser = 1;
	
	const JpushSendStatusNone = 0;
	const JpushSendStatusOk = 1;
	const JpushSendStatusFail = 2;
	const JpushSendStatusCanceled = 3;
}