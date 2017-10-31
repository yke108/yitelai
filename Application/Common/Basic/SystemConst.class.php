<?php
namespace Common\Basic;

class SystemConst{
	const PushTypeJpush = 1; //发App推送消息
	const PushTypeWeixin = 2; //发微信模板消息
	const PushTypeSms = 3; //发送短信
	
	const ConfAndroidVersion = 1;
	const ConfJpush = 23; //极光推送
	const ConfIosVersion = 24; //苹果版本控制
	const ConfAppAbout = 25; //关于我们
	
	const ClientTypeNone = 0; //不限
	const ClientTypeAndroid = 3; //安卓
	const ClientTypeIOS = 4; //苹果

	const JPushPrefix = 'yitelai'; //极光推送用户识别前缀
	
	const SysAdmin = 1; //平台
	const SysDistributor = 2; //
}