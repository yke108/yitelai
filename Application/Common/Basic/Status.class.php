<?php
namespace Common\Basic;

class Status{
	//平台类型
	const SysIdPlatform = 1; //平台
	const SysIdDistributor = 2; //店铺
	const SysIdInformation = 3; //资讯平台
	const SysIdGallery = 4; //图库
	const SysIdBrand = 5; //品牌商
	
	const TaskStatusNone = 0; //未处理
	const TaskStatusDone = 100; //任务已完成
	
	//品牌商状态
	const BrandStatusNone = 0; //未审核
	const BrandStatusNormal = 1; //正常
	const BrandStatusClose = 2; //关闭
	static $brandStatusList = array(
			//self::BrandStatusNone =>'未审核',
			self::BrandStatusNormal => '正常',
			self::BrandStatusClose => '关闭',
	);
	
	//店铺状态
	const DistributorStatusNone = 0; //未审核
	const DistributorStatusNormal = 1; //正常
	const DistributorStatusClose = 2; //关闭
	static $distributorStatusList = array(
			//self::DistributorStatusNone =>'未审核',
			self::DistributorStatusNormal => '正常',
			self::DistributorStatusClose => '关闭',
	);
	
	//管理员类型
	const AdminTypeNormal = 0; //普通管理员
	const AdminTypePreSale = 1; //售前客服
	const AdminTypeAfterSale = 2; //售后客服
	const AdminTypeVisit = 3; //安装回访
	const AdminTypeComplaint = 4; //投诉客服
	static $adminTypeList = array(
			//self::AdminTypeNormal =>'普通管理员',
			self::AdminTypePreSale => '售前客服',
			self::AdminTypeAfterSale => '售后客服',
			self::AdminTypeVisit => '安装回访',
			self::AdminTypeComplaint => '投诉客服',
	);
	
	//预约设计师状态
	const DesignerOrderStatusNone = 0; //未处理
	const DesignerOrderStatusBuildFile = 1; //建档成功
	const DesignerOrderStatusMeasure = 2; //量尺完毕
	const DesignerOrderStatusPlanConfirm = 3; //方案确认
	const DesignerOrderStatusComment = 4; //用户评价
	static $designerOrderStatusList = array(
			self::DesignerOrderStatusNone =>'未处理',
			self::DesignerOrderStatusBuildFile => '建档成功',
			self::DesignerOrderStatusMeasure => '量尺完毕',
			self::DesignerOrderStatusPlanConfirm => '方案确认',
			self::DesignerOrderStatusComment => '用户评价',
	);
	
	const AccountTypeMoney = 1;
	//const AccountTypePoints = 2;
	
	const ChangeTypePayOrder = 10; //支付订单
	const ChangeTypeCommission = 11; //获得分利
	const ChangeTypeCashApply = 12; //申请提现
	const ChangeTypeBackCashApply = 13; //提现失败
	const ChangeTypeOrderBack = 14; //退货退款
	const ChangeTypeOrderTeamBack=15; //拼团失败退款
	const ChangeTypeLottery = 16; //抽奖获得
	const ChangeTypeRecharge = 17; //充值
	const ChangeTypeReward = 18; //文章打赏
	const ChangeTypeGetReward = 19; //获得打赏
	const ChangeTypePayDeposit = 20; //支付保证金
	const ChangeTypePayService = 21; //支付技术服务费
	static $ctypes = array(
			self::ChangeTypePayOrder=>'支付订单',
			self::ChangeTypeCommission=>'获得分利',
			self::ChangeTypeCashApply=>'申请提现',
			self::ChangeTypeBackCashApply=>'提现失败',
			self::ChangeTypeOrderBack=>'退货退款',
			self::ChangeTypeOrderTeamBack=>'拼团失败退款',
			self::ChangeTypeLottery=>'抽奖获得',
			self::ChangeTypeRecharge=>'充值',
			self::ChangeTypeReward=>'文章打赏',
			self::ChangeTypeGetReward=>'获得打赏',
			self::ChangeTypePayDeposit=>'支付保证金',
			self::ChangeTypePayService=>'支付技术服务费',
	);
	
	//const UserTypeNormal = 0; //普通会员
	const UserTypeNormal = 1; //普通会员
	const UserTypeDistributorman = 2; //分销员
	const UserTypeSalesman = 3; //业务员
	const UserTypeRegion = 4; //区域经理
	const UserTypeMarket = 5; //招商经理
	const UserTypeServer = 6; //客服
	static $userTypeList = array(
			self::UserTypeNormal=>'普通会员',
			self::UserTypeDistributorman=>'分销员',
			self::UserTypeSalesman=>'业务员',
			self::UserTypeRegion=>'区域经理',
			self::UserTypeMarket=>'招商经理',
			self::UserTypeServer=>'客服',
	);
	
	const CouponUsedNo = 0; //未使用
	const CouponUsedYes = 1;  //已使用
	
	const OrderServiceNone = 0; //未完成
	const OrderServiceDone = 1; //已完成
	
	//订单类型
	const OrderTypeNormal = 0; //普通订单
	const OrderTypeGroup = 1; //团购订单
	const OrderTypeCustom = 2; //定制订单
	
	//发货状态
	const ShippingStatusNone = 0; //未发货
	const ShippingStatusPrepare = 1; //待发货
	const ShippingStatusDelivering = 2; //已发货、待收货
	const ShippingStatusReceived = 3; //已收货
	const ShippingStatusBacking = 4; //退货中
	const ShippingStatusBacked = 5; //已退货
	
	//支付状态
	const PayStatusNone = 0; //未付款
	const PayStatusPaid = 1; //已付款
	const PayStatusCOD = 2; //货到付款
	const PayStatusRepaying = 3; //退款中
	const PayStatusRepaid = 4; //已退款
	static $payStatusList = array(
			self::PayStatusNone=>'未付款',
			self::PayStatusPaid=>'已付款',
			self::PayStatusCOD=>'货到付款',
			self::PayStatusRepaying=>'退款中',
			self::PayStatusRepaid=>'已退款',
	);
	
	//申请特批
	const DelayPayApply = 1; //申请特批
	const DelayPayDisagree = 2; //不同意特批
	const DelayPayAgree = 3; //同意特批
	
	//订单状态
	const OrderStatusNone = 0; //未确认
	const OrderStatusOnWay = 1; //进行中
	const OrderStatusSuccess = 2; //已完成
	const OrderStatusCancel = 3; //已取消
	const OrderStatusOnBack = 4; //退货中
	const OrderStatusOver = 5; //已结束
	const OrderStatusOnRepair = 6; //维修中
	
	//定制单支付状态
	const CustomPayStatusNone = 0; //未付定金
	const CustomPayStatusPaid = 1; //待付全款
	const CustomPayStatusPaidAll = 2; //已付全款
	static $customPayStatusList = array(
			self::CustomPayStatusNone => '未付定金',
			self::CustomPayStatusPaid => '待付全款',
			self::CustomPayStatusPaidAll => '已付全款',
	);
	
	//定制单状态
	const CustomOrderStatusDesign = 0; //待设计、测量
	const CustomOrderStatusPendingCheck = 1; //生产待审
	const CustomOrderStatusCheckNoPass = 2; //资料审核不通过
	const CustomOrderStatusCheckPass = 3; //资料审核通过
	const CustomOrderStatusChecked = 4; //成本审核完成
	const CustomOrderStatusConfirmed = 5; //店铺确认完成
	const CustomOrderStatusPendingProduce = 6; //待生产
	const CustomOrderStatusProducing = 7; //生产中
	const CustomOrderStatusProduced = 8; //已生产
	const CustomOrderStatusStorage = 9; //已入库
	const CustomOrderStatusShipped = 10; //已发货
	const CustomOrderStatusReceived = 11; //已收货
	const CustomOrderStatusPendingInstall = 12; //待安装
	const CustomOrderStatusInstalled = 13; //已安装
	const CustomOrderStatusFinish = 14; //已完成
	const CustomOrderStatusComment = 15; //已评论
	const CustomOrderStatusCancel = 16; //取消
	static $customOrderStatusList = array(
			self::CustomOrderStatusDesign => '待设计、测量',
			self::CustomOrderStatusPendingCheck => '生产待审',
			self::CustomOrderStatusCheckNoPass => '资料审核不通过',
			self::CustomOrderStatusCheckPass => '资料审核通过',
			self::CustomOrderStatusChecked => '成本审核完成',
			self::CustomOrderStatusConfirmed => '店铺确认完成',
			self::CustomOrderStatusPendingProduce => '待生产',
			self::CustomOrderStatusProducing => '生产中',
			self::CustomOrderStatusProduced => '已生产',
			self::CustomOrderStatusStorage => '已入库',
			self::CustomOrderStatusShipped => '已发货',
			self::CustomOrderStatusReceived => '已收货',
			self::CustomOrderStatusPendingInstall => '待安装',
			self::CustomOrderStatusInstalled => '已安装',
			self::CustomOrderStatusFinish => '已完成',
			self::CustomOrderStatusComment => '已评论',
			self::CustomOrderStatusCancel => '取消',
	);
	
	const CommnetStatusNo = 0; //未评论
	const CommentStatusYes = 1; //已评论
	const CommentStatusAll = 2; //全部商品已评论
	
	//积分兑换商品状态
	const GiftShippingWait=0;//待发货
	const GiftShippingSend=1;//已发货
	const GiftShippingDelivery=2;//已收货
	
	const GiftStatusNone = 0; //未审核
	const GiftStatusPass = 1; //审核通过
	const GiftStatusNoPass = 2; //审核不通过
	static $giftStatusList = array(
			self::GiftStatusNone => '未审核',
			self::GiftStatusPass => '审核通过',
			self::GiftStatusNoPass => '审核不通过',
	);
	
	//提现状态
	const CashWait = 0; //待审核
	const CashNotAgree = 1; //审核不通过
	const CashAgree = 2; //审核通过
	const CashRemit = 3; //打款成功
	const CashRemitFail = 4; //打款失败
	static $cashStatusList = array(
			self::CashWait => '未审核',
			self::CashNotAgree => '审核不通过',
			self::CashAgree => '审核通过',
			self::CashRemit => '打款成功',
			self::CashRemitFail => '打款失败',
	);
	
	//售后类型
	const AfterSaleExchange = 1;
	const AfterSaleBack = 2;
	const AfterSaleRepair = 3;
	
	//文章类型
	const PageTypeNormal = 1; //普通文章页（可删除）
	const PageTypeSystem = 2; //系统文章（不可删除）
	const PageTypeMerchant = 3; //商家入驻（不可删除）
	
	//商家入驻申请状态
	const MerchantStatusOn = 0; //申请中
	const MerchantStatusPass = 1; //申请通过
	const MerchantStatusNoPass = 2; //申请不通过
	const MerchantStatusCancel = 3; //撤消申请
	
	//新闻类型
	const NewsTypeNews = 0; //新闻
	const NewsTypeVideo = 1; //视频
	const NewsTypeSubject = 2; //专题
	static $newsTypeList = array(
			self::NewsTypeNews =>'新闻',
			self::NewsTypeVideo => '视频',
			self::NewsTypeSubject =>'专题',
	);
	
	//新闻展示类型
	const NewsTypeShow1 = 1; //多图
	const NewsTypeShow2 = 2; //单图
	const NewsTypeShow3 = 3; //广告
	static $newsTypeShowList = array(
			self::NewsTypeShow1 =>'多图',
			self::NewsTypeShow2 => '单图',
			self::NewsTypeShow3 =>'广告',
	);
	
	//消息类型
	const MsgTypeNotice = 0; //公告信息
	const MsgTypeShipping = 1; //发货信息
	const MsgTypeRegion = 2; //区域信息
	const MsgTypePayOrder = 3; //支付信息
	const MsgTypeRecharge = 4; //充值信息
	const MsgTypeCash = 5; //提现信息（用户）
	const MsgTypeMerchantApplyPass = 6; //商家入驻申请审核通过
	const MsgTypeDistributorAccount = 7; //店铺账号密码
	const MsgTypeDistributorCash = 8; //提现信息（店铺）
	static $msgTypeList = array(
			self::MsgTypeNotice =>'公告信息',
			self::MsgTypeShipping => '发货信息',
			self::MsgTypeRegion =>'区域信息',
			self::MsgTypePayOrder =>'支付信息',
			self::MsgTypeRecharge =>'充值信息',
			self::MsgTypeCash =>'提现信息',
			self::MsgTypeMerchantApplyPass =>'商家入驻申请审核通过',
			self::MsgTypeDistributorAccount =>'店铺账号密码',
			self::MsgTypeDistributorCash =>'店铺提现',
	);
	
	//菜谱难度
	const CookDifficulty1 = 1; //1星
	const CookDifficulty2 = 2; //2星
	const CookDifficulty3 = 3; //3星
	const CookDifficulty4 = 4; //4星
	static $cookDifficultyList = array(
			self::CookDifficulty1 =>'1星',
			self::CookDifficulty2 => '2星',
			self::CookDifficulty3 =>'3星',
			self::CookDifficulty4 =>'4星',
	);
	
	//菜谱人数
	const CookNumber1 = 1; //1人
	const CookNumber2 = 2; //2人
	const CookNumber3 = 3; //3人
	const CookNumber4 = 4; //4人
	static $cookNumberList = array(
			self::CookNumber1 =>'1人',
			self::CookNumber2 => '2人',
			self::CookNumber3 =>'3人',
			self::CookNumber4 =>'4人以上',
	);
	
	//准备时间
	const CookPrepareTime10 = 10; //10分钟
	const CookPrepareTime20 = 20; //20分钟
	const CookPrepareTime30 = 30; //30分钟
	const CookPrepareTime40 = 40; //40分钟
	const CookPrepareTime50 = 50; //50分钟
	const CookPrepareTime60 = 60; //1小时以上
	static $cookPrepareTimeList = array(
			self::CookPrepareTime10 =>'10分钟',
			self::CookPrepareTime20 => '20分钟',
			self::CookPrepareTime30 =>'30分钟',
			self::CookPrepareTime40 =>'40分钟',
			self::CookPrepareTime50 =>'50分钟',
			self::CookPrepareTime60 =>'1小时以上',
	);
	
	//烹饪时间
	const CookTime10 = 10; //10分钟
	const CookTime20 = 20; //20分钟
	const CookTime30 = 30; //30分钟
	const CookTime40 = 40; //40分钟
	const CookTime50 = 50; //50分钟
	const CookTime60 = 60; //1小时以上
	static $cookTimeList = array(
			self::CookTime10 =>'10分钟',
			self::CookTime20 => '20分钟',
			self::CookTime30 =>'30分钟',
			self::CookTime40 =>'40分钟',
			self::CookTime50 =>'50分钟',
			self::CookTime60 =>'1小时以上',
	);
	
	//来源
	const FromOuter = 'outer'; //外网
	const FromPc = 'pc'; //PC
	const FromWeixin = 'weixin'; //微信
	const FromRecommend = 'recommend'; //微信推荐
	const FromQrcode = 'qrcode'; //二维码
	const FromArticle = 'article'; //文章
	const FromChair = 'chair'; //讲座
	const FromActivity = 'activity'; //扫楼活动
	const FromTel = 'tel'; //购买电话
	const FromStore = 'store'; //线下卖场
	const FromSign = 'sign'; //在线报名
	const FromAndroid = 'android'; //APP添加
	static $fromList = array(
			self::FromOuter =>'外网',
			self::FromPc =>'官网',
			self::FromWeixin => '微信',
			self::FromRecommend => '微信推荐',
			self::FromQrcode => '二维码',
			self::FromArticle => '文章',
			self::FromChair => '讲座',
			self::FromActivity => '扫楼活动',
			self::FromTel => '购买电话',
			self::FromStore => '线下卖场',
			self::FromSign => '在线报名',
			self::FromAndroid => 'App添加',
	);
	
	//罚款状态
	const FineStatusNone = 0; //发布罚款
	const FineStatusAppeal = 1; //申诉中
	const FineStatusAgree = 2; //申诉失败
	const FineStatusCancel = 3; //撤消罚款
	static $fineStatusList = array(
			self::FineStatusNone =>'发布罚款',
			self::FineStatusAppeal => '申诉中',
			self::FineStatusAgree => '申诉失败',
			self::FineStatusCancel => '撤消罚款',
	);
	
	//罚款日志
	const RefTypeAdmin = 1; //平台管理员
	const RefTypeUser = 2; //用户
	
	//抢客户
	const GrabTypeApp = 1; //店铺抢
	const GrabTypePlatform = 2; //平台指派
	const GrabTypeTransfer = 3; //转移
	static $grabTypeList = array(
			self::GrabTypeApp =>'店铺抢',
			self::GrabTypePlatform => '平台指派',
			self::GrabTypeTransfer => '转移',
	);
	
	//订单日志类型
	const OrderLogRefTypeUser = 1; //用户
	const OrderLogRefTypeDistributor = 2; //店铺
	const OrderLogRefTypePlatform = 3; //平台
	
	const OrderLogTypeCreate = 0; //提交订单
	const OrderLogTypePay = 1; //已付定金
	const OrderLogTypeCancel = 2; //取消订单
	const OrderLogTypeShipping = 3; //订单发货
	const OrderLogTypeBack = 4; //订单退货
	const OrderLogTypeDrawing = 5; //测量与设计
	const OrderLogTypeCheckDrawing = 6; //审核资料
	const OrderLogTypeOffer = 7; //已测量上传方案
	const OrderLogTypePayAll = 8; //已付全款
	const OrderLogTypeProducing = 9; //生产中
	const OrderLogTypeProduced = 10; //已生产
	const OrderLogTypeStorage = 11; //已入库
	const OrderLogTypeShipped = 12; //已发货
	const OrderLogTypeReceived = 13; //已签收
	const OrderLogTypeInstalled = 14; //已安装
	const OrderLogTypeConfirmed = 15; //已确认
	const OrderLogTypeFinish = 16; //已完成
	static $orderLogTypeList = array(
			self::OrderLogTypeCreate =>'提交订单',
			self::OrderLogTypePay =>'已付定金',
			self::OrderLogTypeCancel => '取消订单',
			self::OrderLogTypeShipping => '订单发货',
			self::OrderLogTypeBack => '订单退货',
			self::OrderLogTypeDrawing => '测量与设计',
			self::OrderLogTypeCheckDrawing => '审核资料',
			self::OrderLogTypeOffer => '已测量上传方案',
			self::OrderLogTypePayAll => '已付全款',
			self::OrderLogTypeProducing => '生产中',
			self::OrderLogTypeProduced => '已生产',
			self::OrderLogTypeStorage => '已入库',
			self::OrderLogTypeShipped => '已发货',
			self::OrderLogTypeReceived => '已签收',
			self::OrderLogTypeInstalled => '已安装',
			self::OrderLogTypeConfirmed => '已确认',
			self::OrderLogTypeFinish => '已完成',
	);
	
	//反馈类型
	const FeedbackTypeComplain = 1; //投诉
	const FeedbackTypeSuggest = 2; //建议
	const FeedbackTypeAsk = 3; //咨询
	static $feedbackTypeList = array(
			self::FeedbackTypeComplain =>'投诉',
			self::FeedbackTypeSuggest => '建议',
			self::FeedbackTypeAsk => '咨询',
	);
	
	//留言状态
	const FeedbackStatusNone = 0; //未解答
	const FeedbackStatusOn = 1; //解答中
	const FeedbackStatusDone = 2; //已解答
	static $feedbackStatusList = array(
			self::FeedbackStatusNone =>'未解答',
			self::FeedbackStatusOn => '解答中',
			self::FeedbackStatusDone => '已解答',
	);
	
	//留言来源
	const FeedbackClientPc = 1; //PC
	const FeedbackClientWeixin = 2; //微信
	const FeedbackClientWeChat = 3; //微信公众号
	static $feedbackClientList = array(
			self::FeedbackClientPc =>'官网',
			self::FeedbackClientWeixin => '微信',
			self::FeedbackClientWeChat => '微信公众号',
	);
	
	const FeedbackRefTypeUser = 1; //会员 
	const FeedbackRefTypeAdmin = 2; //管理员
	
	//目标类型
	const WorkTargetDay = 1; //天目标
	const WorkTargetWeek = 2; //周目标
	const WorkTargetMonth = 3; //月目标
	const WorkTargetYear = 4; //年目标
	static $workTargetList = array(
			self::WorkTargetDay =>'天目标',
			self::WorkTargetWeek => '周目标',
			self::WorkTargetMonth => '月目标',
			self::WorkTargetYear => '年目标',
	);
	
	//目标状态
	const WorkFinishStatusOn = 0; //进行中
	const WorkFinishStatusFinish = 1; //已完成
	const WorkFinishStatusBeyond = 2; //超额完成
	const WorkFinishStatusNotFinish = 3; //未完成
	static $workTargetFinishStatusList = array(
			self::WorkFinishStatusNotFinish => '未完成',
			self::WorkFinishStatusOn =>'进行中',
			self::WorkFinishStatusFinish => '已完成',
			self::WorkFinishStatusBeyond => '超额完成',
	);
	
	//目标审核状态
	const WorkCheckStatusNo = 0; //未审核
	const WorkCheckStatusOn = 1; //审核中
	const WorkCheckStatusNoPass = 2; //审核不通过
	const WorkCheckStatusPass = 3; //审核通过
	static $workTargetCheckStatusList = array(
			self::WorkCheckStatusNo =>'未审核',
			self::WorkCheckStatusOn => '审核中',
			self::WorkCheckStatusNoPass => '审核不通过',
			self::WorkCheckStatusPass => '审核通过',
	);
	
	const YES = 1; //是
	const NO = 2; //否
	const None = 0; //未知
}