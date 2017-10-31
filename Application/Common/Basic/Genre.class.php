<?php
namespace Common\Basic;

class Genre{
	const CollectTypeGoods = 0; //商品收藏
	const CollectTypeStore = 1; //店铺收藏
	const CollectTypeGoodsFoot = 2; //商品足迹
	const CollectTypeCompany = 3; //家装公司
	const CollectTypeMaterial = 4; //图库素材
	const CollectTypeNews = 5; //新闻、视频、专题
	const CollectTypeShareStory = 6; //分享文章
	const CollectTypeShareGoods = 7; //分享商品
	const CollectTypeStoryFoot = 8; //文章足迹
	
	const ServPlaceNone = 0; //无需服务
	const ServPlaceStore = 1; //到店服务
	const ServPlaceDoor = 2; //上门服务
	const ServPlaceTel = 3; //电话
	const ServPlaceSms = 4; //短信
	const ServPlaceWeixin = 5; //微信
	
	const CouponTypeValid = 1;//有效优惠券
	const CouponTypeInvalid = 2;//过期优惠券
	const CouponTypeUsed = 3;//已使用
	
	const UserAccountChangeRecharge = 1; //购物充值
	const UserAccountChangeBuy = 2; //购物支出
	const UserAccountChangePointsGiven = 3; //积分赠送
	const UserAccountChangeCashed = 4; //提现支出
	const UserAccountChangePointsEx = 5; //积分兑换支出
	const UserAccountChangeRewardOnce = 6; //下线升级金钻时一次性奖励
	const UserAccountChangeReward = 7; //下线销售商品的获利，金钱或积分
	const UserAccountChangeOnReg = 8; //注册赠送
	const UserAccountChangeSign = 9; //签到
	
	const TaskTypeDoing = 1; //进行中
	const TaskTypeExpire = 2; //已过期
	const TaskTypeDone = 3; //已完成
	
	const TypeValueNo = 0; //否
	const TypeValueYes = 1; //是
	const TypeValueUndefined = 2; //空
	
	const DepotPaperTypeCheck = 1; //盘点单
	const DepotPaperTypeInput = 2; //入库单
	const DepotPaperTypeOutput = 3; //出库单
	
	const StoreTypeReal = 1; //实体店
	const StoreTypeVirtual = 0; //非实体店
	
	const LockStatusNone = 0; //初始状态，未被引用，可编辑、可删除
	const LockStatusUsed = 1; //被引用，不可删除，可部分编辑, 可隐藏
	const LockStatusHide = 2; //被引用，不可删除，不可编辑，隐藏， 可显示
}