<?php
namespace Common\Basic;

class ApplyConst{
	const VerifyStatusStart = 10; //待审批
	const VerifyStatusTransfer = 30; //转出
	const VerifyStatusWaitSignAgain = 50; //待复审
	const VerifyStatusOk = 100; //审批通过
	const VerifyStatusCancel = 110; //已取消
	const VerifyStatusFail = 150; //未通过
	
	const TypeLeave = 1; //请假
	const TypeTrip = 2; //出差
	const TypeExpended = 3; //报销
	const TypePriceCut = 4; //价格特批
	const TypeFile = 5; //事件文件
	const TypeSample = 6; //样板
	const TypeAdFee = 7; //广告支持
	const TypeMoney = 8; //资金支持
	const TypeActivity = 9; //活动支持
	const TypeTrain = 10; //系统培训
	
	static $verify_status_list = [
		self::VerifyStatusStart => '待审批',
		self::VerifyStatusTransfer => '转出',
		self::VerifyStatusWaitSignAgain => '待复审',
		self::VerifyStatusOk => '已批准',
		self::VerifyStatusCancel => '已取消',
		self::VerifyStatusFail => '已驳回',
	];
	
	static $appy_type_list = [
		self::TypeLeave => '请假',
		self::TypeTrip => '出差',
		self::TypeExpended => '报销',
		self::TypePriceCut => '价格特批',
		self::TypeFile => '事件文件',
		self::TypeSample => '样板',
		self::TypeAdFee => '广告支持',
		self::TypeMoney => '资金支持',
		self::TypeActivity => '活动支持',
		self::TypeTrain => '系统培训',
	];
}