GO


CREATE      FUNCTION CGI.checkAuthUserPass(@UserID VARCHAR(25),@Pass VARCHAR(50)) RETURNS INT
as 
BEGIN
DECLARE @UserJID INT
	SET @UserJID = 0
	SELECT @UserJID = JID FROM TB_Net2e WHERE StrUserID = @UserID AND SecondPassword=@Pass
	IF( @@error <
GO

CREATE      FUNCTION  CGI.checkAuthUserPass1(@UserID VARCHAR(25),@Pass VARCHAR(50)) RETURNS INT
as 
BEGIN
DECLARE @UserJID INT
	SET @UserJID = 0
	SELECT @UserJID = JID FROM TB_User WHERE StrUserID = @UserID AND Password=@Pass
	IF( @@error <> 0 or
GO




CREATE   function CGI.getSilkGift(@UserJID INT) RETURNS INT
AS 
BEGIN
DECLARE @gift INT
	SET @gift = 0
	SELECT @gift = silk_gift FROM SK_Silk WHERE JID = @UserJID
	IF( @@error <> 0 OR @@rowcount = 0 OR @gift = 0 OR @gift IS null)
	BEGIN
		RE
GO


CREATE  FUNCTION CGI.getSilkOwn(@UserJID INT) RETURNS INT
as 
BEGIN
DECLARE @own INT
	SET @own = 0
	SELECT @own = silk_own FROM SK_Silk WHERE JID = @UserJID
	IF( @@error <> 0 or @@rowcount = 0 or @own = 0 or @own IS null)
	BEGIN
		RETURN 0
	E
GO



CREATE  FUNCTION CGI.getSilkPoint(@UserJID INT) RETURNS INT
AS
BEGIN
DECLARE @point INT
	SET @point = 0
	SELECT @point = silk_point FROM SK_Silk WHERE JID = @UserJID
	IF( @@error <> 0 or @@rowcount = 0 or @point = 0 or @point IS null)
	BEGIN

GO





CREATE      FUNCTION CGI.getUserJID(@UserID VARCHAR( 128)) RETURNS INT
AS
BEGIN
DECLARE @UserJID INT
	SET @UserJID = 0
	SELECT @UserJID = JID FROM TB_User WHERE StrUserID = @UserID
	IF( @@error <> 0 OR @@rowcount = 0 OR @UserJID = 0 OR @
GO
CREATE function make_ip (@ip1 bigint, @ip2 bigint, @ip3 bigint, @ip4 bigint)
returns int
as
begin
	declare @long_ip bigint
	set @long_ip = @ip1 + (@ip2 * 256) + (@ip3 * 256 * 256) + (@ip4 * 256 * 256 * 256)
	if( @long_ip > 0x7FFFFFFF)
	begin
		s
GO
CREATE  function split_ip (@ip int)
returns varchar(16)
as
begin
	declare @ip1 bigint
	declare @ip2 bigint
	declare @ip3 bigint
	declare @ip4 bigint
	declare @ip_temp bigint
	set @ip_temp = @ip
	if( @ip_temp < 0)
	begin
		set @ip_temp = @ip_
GO
CREATE  procedure _AddArticleData
	@ContentID	tinyint,
	@Subject	varchar(80),
	@Article		varchar(1024)
as
	insert _Notice values( @ContentID, @Subject, @Article, getdate())
GO
create  PROCEDURE dbo._AddCurrentUserCount
	@nShardID 	INT,
	@nUserCount 	INT,
	@dLogDate	DATETIME
AS
BEGIN 
	INSERT INTO _ShardCurrentUser (nShardID, nUserCount, dLogDate) VALUES (@nShardID, @nUserCount, @dLogDate)
END

GO
CREATE procedure _AddUser 
	@szUserID	varchar(128),
	@szPassword	varchar( 32),
	@szName	varchar( 16),
	@SSNumber1	varchar( 32),
	@SSNumber2	varchar( 32),
	@szOrganization	varchar( 128),
	@szRoleDesc	varchar( 128),
	@nPrimarySecurityID tinyint,

GO
-- 2. Procedure 수정
CREATE PROCEDURE [dbo]._CAS_AddItem
	@nCategory	tinyint,
	@wShardID	smallint,
	@dwUserJID	int,
	@szCharName	varchar(64),
	@szTgtCharName	varchar(64),
	@szMailAddress	varchar(40),	
	@szStatement	varchar(512),
	@szChatLog	varch
GO
-- 단순히 카타고리를 변경한다.
CREATE PROCEDURE [dbo]._CAS_ChangeCategory
	@nSerial 	int,
	@nCategory 	tinyint
AS
	UPDATE _CasData SET nCategory = @nCategory WHERE nSerial = @nSerial
	IF (@@error <> 0 or @@rowcount = 0)
	BEGIN
		SELECT -1
		RETURN
	END
	
GO
-- CAS아이템의 처리 책임을 지우고/버린다.
CREATE PROCEDURE [dbo]._CAS_ChangeStatus
	@btOp		tinyint,
	@nSerial		int,
	@szGM		varchar(20),
	@szMemo	varchar(128)
AS
	-- Issued		0
	-- Processing		1
	-- Completed		2
	-- UnableToProcess	3 
	IF @btOp = 1	-- TAKE C
GO
--
CREATE PROCEDURE [dbo]._CAS_CompleteItem
	@nSerial 		int,
	@szProcessedGM	varchar(20),
	@szMemo		varchar(128),
	@szAnswer		varchar(1024)
AS
	BEGIN TRANSACTION
	DECLARE @wShardID	smallint
	DECLARE @szChar	varchar(64)
	SELECT @wShardID = wSha
GO
--
CREATE PROCEDURE [dbo]._CAS_GetAnswerList
	@szCharName	varchar(64)
AS
	
	SELECT nSerial,  szProcessedGM, szAnswer, dProcessDate
	FROM _CasData WITH (NOLOCK) 
	WHERE szCharName = @szCharName AND nStatus = 2 AND btUserChecked = 0	-- 해당 캐릭터의 처리된 
GO
CREATE PROCEDURE [dbo]._CAS_GetItemList
AS
	SELECT * 
	FROM _CasData
	WHERE nStatus < 2   	-- 처리대기 중/처리중 인 녀석들만 가져온다. 즉, 처리 완료나 처리불가는 가져오지 않는다.

GO
-- 
CREATE PROCEDURE [dbo]._CAS_GMCallLog
	@nSerial			int,
	@szProcessedGM	varchar(20),
	@wShardID		smallint,
	@szChar		varchar(64),
	@szGMChatLog		varchar(4000)
AS
	INSERT INTO _CasGMChatLog VALUES (@szProcessedGM, @wShardID, @szChar, @nSerial,
GO
-- 유저가 확인한 것으로 체크한다.
CREATE PROCEDURE [dbo]._CAS_MarkItemDeleted
	@nSerial		int
AS
	IF NOT EXISTS(SELECT nCategory FROM _CasData WHERE nSerial = @nSerial)
	BEGIN
		SELECT -1
		RETURN
	END
	UPDATE _CasData SET btUserChecked = 1 WHERE  nSerial = 
GO
--
CREATE  PROCEDURE [dbo]._CAS_Search
	@dBeginDate	datetime,
	@dEndDate	datetime,
	@szAccount	varchar(128),
	@szReporter	varchar(64),
	@szTarget	varchar(64),
	@szCondition	varchar(1024)
AS	
	-- 먼저 Account가 존재하면, jid를 찾아서 조건식을 만든다.
	DECLARE @s
GO
-- CAS GMCall Search!!
CREATE PROCEDURE [dbo]._CAS_Search_GMCall
	@wShardID	smallint,
	@btOpCode	tinyint,
	@szString	varchar(128),
	@dBeginDate	datetime,
	@dEndDate	datetime
AS
	-- CAS_GMCALL_SEARCH_BY_GM		(BYTE)0x00
	-- CAS_GMCALL_SEARCH_BY_CH
GO
CREATE PROCEDURE [dbo]._CAS_Statistics
	@btCategory	tinyint,
	@dBeginDate	datetime,
	@dEndDate	datetime
AS
	--CAS_STATISTICS_YESTERDAY			(BYTE)0x00	// 전일 통계
	--CAS_STATISTICS_LASTWEEK			(BYTE)0x01	// 7일간 통계
	--CAS_STATISTICS_BY_HOUR			(BYTE)0x02	
GO


CREATE    PROCEDURE _CertifyTB_User
	@szUserID	varchar(25),
	@szPassword	varchar(50)
AS
	declare @nUserJID int
	declare @sec_primary tinyint
	declare @sec_content tinyint
	set	@nUserJID		= 0
	set	@sec_primary		= 0
	set	@sec_content		= 0
--@@
GO
CREATE procedure _CertifyUser
	@szUserID		varchar(25),
	@szPassword	varchar(50),
	@szSSNumber	varchar(32)
as
	declare @nUserJID int
	declare @sec_primary tinyint
	declare @sec_content tinyint
	set	@nUserJID		= 0
	set	@sec_primary	= 0
	set	@sec
GO





CREATE  procedure _ConsumeSilkByGameServer

@JID 		int,
@Silk_Offset_Own	int OUTPUT,
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
--		¸¶ÀÏ¸®Áö (ÃÖ¼±È£)
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
@Silk_Offset_Gift	int OUTPUT,
@Mileage_
GO



CREATE      procedure _ConsumeSilkByGameServer2

@JID 		int,
@Silk_Offset_Own	int OUTPUT,
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
--		마일리지 (최선호)
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
@Silk_Offset_Gift	int OUTPUT,
@Mileage_Offs
GO
create procedure _DeleteArticleData
	@ID	int
as
	delete _Notice where ID = @ID

GO
CREATE procedure _DeleteUser
	@nID	int
as
	delete TB_User where JID = @nID

GO
CREATE procedure _EditUser 
	@nID		int,
	@szPassword	varchar( 32),
	@szName	varchar( 16),
	@SSNumber1	varchar( 32),
	@SSNumber2	varchar( 32),
	@szOrganization	varchar( 128),
	@szRoleDesc	varchar( 128),
	@nPrimarySecurityID tinyint,
	@nContentSe
GO
CREATE PROCEDURE [dbo]._GetArticleData
AS
             SELECT TOP 4 ContentID, Subject, Article, EditDate
             FROM _Notice
             ORDER BY EditDate DESC

GO
CREATE PROCEDURE dbo._GetConcurrentUserLog
	@wShardID	SMALLINT,
	@btPeriod	TINYINT,
	@dBeginDate	DATETIME,
	@dEndDate	DATETIME
AS
	
	-- QUERY_PERIOD_HOUR		(BYTE)0x00
	-- QUERY_PERIOD_DAY		(BYTE)0x01
	-- QUERY_PERIOD_WEEK		(BYTE)0x02
	-- QUERY_
GO




CREATE    PROCEDURE _GetSilkDataForGameServer  
@UserJID int,  
@SilkOwn int output,  
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  
--  마일리지 최선호)    
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  
@SilkGift  int output,  
@Mileage i
GO

CREATE    PROCEDURE _GetSilkDataForGameServer_bak
@UserJID int,  
@SilkOwn int output,  
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  
--  마일리지 최선호)    
--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  
@SilkGift  int output,  
@Mileage int o
GO
CREATE PROC _GetSilkHistory @UserJID int,  
       @ReqType int,  
       @ReqPeriod int  
AS  
  
DECLARE @strType VARCHAR(100)  
DECLARE @strPeriod VARCHAR(100)  
  
SET @strType = ''  
SET @strPeriod = ''  
  
  
IF @ReqType = 1  
BEGIN 
GO
CREATE PROCEDURE dbo._GetSMLog
	@nCatagory	tinyint,
	@szUserID	varchar(128),
	@szBeginDate	datetime,
	@szEndDate	datetime
as
	IF LEN(@szUserID) > 0
	BEGIN
		IF @nCatagory = 0 -- catagory 가 0이면 All 이다.
		BEGIN
			select top 100 * from _SMCLog w
GO
create procedure _GetUserInfo 
	@JID	int
as
	select convert( varchar( 20), Name), convert( varchar(6), ''), convert( varchar(16), phone), convert( varchar(16), mobile), convert( varchar( 70), email)
	from TB_User where JID = @JID

GO
CREATE  procedure _GetUserJID
	@UserID		varchar( 128)
as
	declare @UserJID int
	set @UserJID = 0
	select @UserJID = JID from TB_USer where StrUserID = @UserID
	if( @@error <> 0 or @@rowcount = 0 or @UserJID = 0 or @UserJID is null)
	begin
		sele
GO
create procedure _InitCharStat
	@CharName	varchar(16),
	@Level		int,
	@RemainSkillPoint int = 500000,
	@RemainGold int = 100000000
as
	declare @charid int
	set @charid = 0
	select @charid = charid from _Char where CharName16 = @CharName
	if( @@
GO
CREATE PROC _IPStatistics	@StartDate smalldatetime,
							@EndDate smalldatetime
AS
	select nJID, nIP from _LoginLogoutStatistics where nIDX in (
		select max(nIdx) as nIDX from _LoginLogoutStatistics 
		where dlogin < @EndDate
		and dlogout > @S
GO
CREATE PROC _IPStatisticsOnUserLogin
	@dwJID	int,
	@dwIP	int
AS
	declare @nRowIDX	int
	insert into _LoginLogoutStatistics values (@dwJID, @dwIP, getdate(), getdate(), 0)
	set @nRowIDX = @@identity
	select @nRowIDX

GO
CREATE PROC _IPStatisticsOnUserLogout
	@nRowIDX	int
AS
	update _LoginLogoutStatistics set dLogout = getdate(), byReserved = 1 where nIdx = @nRowIDX

GO


CREATE procedure _ManageShardCharacter
	@job	tinyint,
	@ShardID   smallint,
	@UserJID   int,
	@SlotIndex tinyint,
	@CharName varchar(17)
as
	if( @job = 0)
	begin
		if( not exists( select * from SR_CharNames where UserJID = @UserJID and ShardI
GO
CREATE procedure [dbo].[_ManageShardCharName]
	@job		tinyint,
	@UserJID   	int,
	@ShardID   	smallint,
	@CharName 	varchar(64),
	@OldName 	varchar(64)
as
	-- add new char name
	if (@job = 0)
	begin
		if (not exists(select * from SR_ShardCharNa
GO
CREATE PROCEDURE [dbo]._QueryActivePunishmet
AS
             DECLARE @DateNow        DATETIME
             SET @DateNow = GETDATE()
 
             SELECT UserJID, Type, timeBegin, timeEnd FROM _BlockedUser WITH (NOLOCK)
             -- Login블럭이 아니
GO

CREATE    procedure [dbo]._QueryBlockGuide
	@userjid	int	
as
	declare @SerialNo int
	select @SerialNo = SerialNo from _BlockedUser with( NOLOCK) where UserJID = @userjid and Type in (1, 2)
	if( @@rowcount = 0 or @@error <> 0 or @SerialNo = 0 or @S
GO
CREATE     procedure _QueryCharOwnedUser
	@CharName	varchar(17)
as
	create table #user_jid_table
	(
		user_jid int
	)
	declare @UserJID int
	declare record_cursor cursor LOCAL FAST_FORWARD for select UserJID from SR_CharNames where
			CharID_1 
GO
create procedure _QueryUser 
	@QueryString varchar(128)
as
	select top 100  JID, convert( varchar(128), StrUserID), convert( varchar( 32), password), convert( varchar(16), Name),
		convert( varchar(32), ''), convert( varchar(32), ''), convert( varch
GO
CREATE     procedure _QueryUserByUserID
	@UserID		varchar(128)
as
	select convert( varchar(128), TB_User.StrUserID), convert( varchar( 20), TB_User.Name), SR_CharNames.ShardID, SR_CharNames.CharID_1, SR_CharNames.CharID_2, SR_CharNames.CharID_3,
		c
GO

CREATE PROCEDURE [dbo].[_RegisterAutomatedPunishment]
	@Account	VARCHAR(128),
	@Type	  	TINYINT,
	@Executor	VARCHAR(128),
	@Guide		VARCHAR(512),
	@Description	VARCHAR(1024),
	@BlockTimeElapse	INT
--	@BlockStartTime	DATETIME,
--	@BlockEndTime	DA
GO
-- 
-----------------------------------------------------------------------------------
-----------------------------------------------------------------------------------
CREATE procedure _RegisterPunishment
	@UserJID 	int,
	@Type	  	tinyint,
	@E
GO

create PROCEDURE [dbo].[_TradeAutoBlock] 
    @CharID INT, 
	@Executor	VARCHAR(128),
	@Guide		VARCHAR(512),
	@Description	VARCHAR(1024),
	@BlockTimeElapse	INT
AS 

	------------------------------------------------------------------------------
GO
CREATE   procedure _UpdateArticleData
	@ID	 int,
	@Subject varchar(80),
	@Article varchar(1024)
as
	update _Notice set Subject = @Subject, Article = @Article where ID = @ID

GO
--
CREATE procedure _UpdatePunishment
	@SerialNo	int,
	@UserJID	int,
	@Type		int,
	@Executor	varchar(32),
	@Shard		smallint,
	@CharName	varchar(32),
	@CharInfo	varchar(256),
	@PosInfo	varchar(64),
	@Guide		varchar(512),
	@Description	varchar(
GO

CREATE PROCEDURE [dbo].[_UpdateSiegeFortressStatus]
	@ShardID			INT,
	@FortressName			VARCHAR(129),
	@FortressScale			TINYINT,
	@TaxRatio			SMALLINT,	
	@OwnerGuildName			VARCHAR(129),
	@OwnerGuildMaster		VARCHAR(129),
	@OwnerAllianceGuildName1	V
GO

CREATE PROCEDURE [dbo].[_UpdateSiegeFortressTaxRatio]
	@ShardID		INT,
	@FortressName		VARCHAR(129),
	@TaxRatio		SMALLINT
AS
	IF EXISTS (SELECT FortressName FROM [dbo].[__SiegeFortressStatus__] WITH (NOLOCK) WHERE FortressName = @FortressName and S
GO

CREATE PROCEDURE [dbo].[_UpdateTrijobRankingStatus]
	@ShardID	INT,
	@Status		TINYINT
AS
	IF EXISTS (SELECT ShardID FROM __TrijobRankingStatus__ WHERE ShardID = @ShardID)
	BEGIN
		-- °»½Å ½Ã°£Àº ValidateµÈ ½Ã°£¸¸ À¯ÀÇ¹ÌÇÏ´Ù.
		IF @Status = 1
		B
GO
CREATE   procedure _ViewContentUser
	@condition tinyint,
	@id_wildcard varchar( 64)
as
if( @condition = 1)
begin
	create table #temp
	(
		UserJID		int,
		UserID		varchar(128),
		BlockType	tinyint,
		BlockBeginTime	datetime,
		BlockEndTime	da
GO
CREATE                  PROCEDURE CGI.CGI_SubtractSilk_VAS
	@RefundID VARCHAR(25),
	@UserID   VARCHAR(25),
	@Password   VARCHAR(50),
	@PkgID   INT,
	@NumSilk INT,
	@Price INT
as
	
	DECLARE @UserJID INT
	DECLARE @SilkRemain INT
	-- DECLARE @Po
GO
CREATE PROCEDURE CGI.CGI_VTCWebPurchaseSilk
	@OrderID VARCHAR(25), 
	@UserID   VARCHAR(25), 
	@PkgID   INT, 
	@NumSilk INT, 
	@Price INT, 
	@PartnerTransID Bigint,
	@PGCompany tinyint,
	@ClientUser Varchar(25),
	@ClientIPA Varchar(15)
as
	DEC
GO


CREATE   PROCEDURE CGI.CGI_WebGetTotalSilk
as
	DECLARE @own FLOAT
	SET @own = 0
	SELECT @own = sum(silk_own) FROM SK_Silk
	IF( @@error <> 0 or @@rowcount = 0 or @own = 0 or @own IS null)
		BEGIN
			SELECT Result = -1
			RETURN
		END
	ELSE
	
GO

CREATE                   PROCEDURE CGI.CGI_WebPurchaseSilk
	@OrderID VARCHAR(25),
	@UserID   VARCHAR(25),
	@PkgID   INT,
	@NumSilk INT,
	@Price INT
as
	DECLARE @UserJID INT
	DECLARE @SilkRemain INT
	--DECLARE @PointRemain INT
	SET @UserJID = 
GO



-- =============================================
-- Author:		Abdelrhman Elbattawy
-- =============================================
CREATE PROCEDURE [CGI].[CGI_WebPurchaseSilk_gift]
	@UserID  INT,
	@NumSilk INT
as
	DECLARE @SilkRemain INT
	
GO

CREATE                  PROCEDURE CGI.CGI_WebRefundSilk
	@RefundID VARCHAR(25),
	@UserID   VARCHAR(25),
	@Password   VARCHAR(50),
	@PkgID   INT,
	@NumSilk INT,
	@Price INT
as
	
	DECLARE @UserJID INT
	DECLARE @SilkRemain INT
	-- DECLARE @Poi
GO
create proc dbo.dt_addtosourcecontrol
    @vchSourceSafeINI varchar(255) = '',
    @vchProjectName   varchar(255) ='',
    @vchComment       varchar(255) ='',
    @vchLoginName     varchar(255) ='',
    @vchPassword      varchar(255) =''

as

s
GO
create proc dbo.dt_addtosourcecontrol_u
    @vchSourceSafeINI nvarchar(255) = '',
    @vchProjectName   nvarchar(255) ='',
    @vchComment       nvarchar(255) ='',
    @vchLoginName     nvarchar(255) ='',
    @vchPassword      nvarchar(255) =''


GO
/*
**	Add an object to the dtproperties table
*/
create procedure dbo.dt_adduserobject
as
	set nocount on
	/*
	** Create the user object if it does not exist already
	*/
	begin transaction
		insert dbo.dtproperties (property) VALUES ('DtgSchem
GO
create procedure dbo.dt_adduserobject_vcs
    @vchProperty varchar(64)

as

set nocount on

declare @iReturn int
    /*
    ** Create the user object if it does not exist already
    */
    begin transaction
        select @iReturn = objecti
GO
create proc dbo.dt_checkinobject
    @chObjectType  char(4),
    @vchObjectName varchar(255),
    @vchComment    varchar(255)='',
    @vchLoginName  varchar(255),
    @vchPassword   varchar(255)='',
    @iVCSFlags     int = 0,
    @iActionFlag   
GO
create proc dbo.dt_checkinobject_u
    @chObjectType  char(4),
    @vchObjectName nvarchar(255),
    @vchComment    nvarchar(255)='',
    @vchLoginName  nvarchar(255),
    @vchPassword   nvarchar(255)='',
    @iVCSFlags     int = 0,
    @iActionF
GO
create proc dbo.dt_checkoutobject
    @chObjectType  char(4),
    @vchObjectName varchar(255),
    @vchComment    varchar(255),
    @vchLoginName  varchar(255),
    @vchPassword   varchar(255),
    @iVCSFlags     int = 0,
    @iActionFlag   int =
GO
create proc dbo.dt_checkoutobject_u
    @chObjectType  char(4),
    @vchObjectName nvarchar(255),
    @vchComment    nvarchar(255),
    @vchLoginName  nvarchar(255),
    @vchPassword   nvarchar(255),
    @iVCSFlags     int = 0,
    @iActionFlag  
GO
CREATE PROCEDURE dbo.dt_displayoaerror
    @iObject int,
    @iresult int
as

set nocount on

declare @vchOutput      varchar(255)
declare @hr             int
declare @vchSource      varchar(255)
declare @vchDescription varchar(255)

    exe
GO
CREATE PROCEDURE dbo.dt_displayoaerror_u
    @iObject int,
    @iresult int
as
	-- This procedure should no longer be called;  dt_displayoaerror should be called instead.
	-- Calls are forwarded to dt_displayoaerror to maintain backward compatibili
GO
/*
**	Drop one or all the associated properties of an object or an attribute 
**
**	dt_dropproperties objid, null or '' -- drop all properties of the object itself
**	dt_dropproperties objid, property -- drop the property
*/
create procedure dbo.d
GO
/*
**	Drop an object from the dbo.dtproperties table
*/
create procedure dbo.dt_dropuserobjectbyid
	@id int
as
	set nocount on
	delete from dbo.dtproperties where objectid=@id

GO
/* 
**	Generate an ansi name that is unique in the dtproperties.value column 
*/ 
create procedure dbo.dt_generateansiname(@name varchar(255) output) 
as 
	declare @prologue varchar(20) 
	declare @indexstring varchar(20) 
	declare @index integer 
GO
/*
**	Retrieve the owner object(s) of a given property
*/
create procedure dbo.dt_getobjwithprop
	@property varchar(30),
	@value varchar(255)
as
	set nocount on

	if (@property is null) or (@property = '')
	begin
		raiserror('Must specify a p
GO
/*
**	Retrieve the owner object(s) of a given property
*/
create procedure dbo.dt_getobjwithprop_u
	@property varchar(30),
	@uvalue nvarchar(255)
as
	set nocount on

	if (@property is null) or (@property = '')
	begin
		raiserror('Must specify
GO
/*
**	Retrieve properties by id's
**
**	dt_getproperties objid, null or '' -- retrieve all properties of the object itself
**	dt_getproperties objid, property -- retrieve the property specified
*/
create procedure dbo.dt_getpropertiesbyid
	@id in
GO
/*
**	Retrieve properties by id's
**
**	dt_getproperties objid, null or '' -- retrieve all properties of the object itself
**	dt_getproperties objid, property -- retrieve the property specified
*/
create procedure dbo.dt_getpropertiesbyid_u
	@id 
GO
create procedure dbo.dt_getpropertiesbyid_vcs
    @id       int,
    @property varchar(64),
    @value    varchar(255) = NULL OUT

as

    set nocount on

    select @value = (
        select value
                from dbo.dtproperties
     
GO
create procedure dbo.dt_getpropertiesbyid_vcs_u
    @id       int,
    @property varchar(64),
    @value    nvarchar(255) = NULL OUT

as

    -- This procedure should no longer be called;  dt_getpropertiesbyid_vcsshould be called instead.
	-- Ca
GO
create proc dbo.dt_isundersourcecontrol
    @vchLoginName varchar(255) = '',
    @vchPassword  varchar(255) = '',
    @iWhoToo      int = 0 /* 0 => Just check project; 1 => get list of objs */

as

	set nocount on

	declare @iReturn int
	decla
GO
create proc dbo.dt_isundersourcecontrol_u
    @vchLoginName nvarchar(255) = '',
    @vchPassword  nvarchar(255) = '',
    @iWhoToo      int = 0 /* 0 => Just check project; 1 => get list of objs */

as
	-- This procedure should no longer be called;
GO
create procedure dbo.dt_removefromsourcecontrol

as

    set nocount on

    declare @iPropertyObjectId int
    select @iPropertyObjectId = (select objectid from dbo.dtproperties where property = 'VCSProjectID')

    exec dbo.dt_droppropertiesb
GO
/*
**	If the property already exists, reset the value; otherwise add property
**		id -- the id in sysobjects of the object
**		property -- the name of the property
**		value -- the text value of the property
**		lvalue -- the binary value of the pr
GO
/*
**	If the property already exists, reset the value; otherwise add property
**		id -- the id in sysobjects of the object
**		property -- the name of the property
**		uvalue -- the text value of the property
**		lvalue -- the binary value of the p
GO
create proc dbo.dt_validateloginparams
    @vchLoginName  varchar(255),
    @vchPassword   varchar(255)
as

set nocount on

declare @iReturn int
declare @iObjectId int
select @iObjectId =0

declare @VSSGUID varchar(100)
select @VSSGUID = 'SQ
GO
create proc dbo.dt_validateloginparams_u
    @vchLoginName  nvarchar(255),
    @vchPassword   nvarchar(255)
as

	-- This procedure should no longer be called;  dt_validateloginparams should be called instead.
	-- Calls are forwarded to dt_validate
GO
create proc dbo.dt_vcsenabled

as

set nocount on

declare @iObjectId int
select @iObjectId = 0

declare @VSSGUID varchar(100)
select @VSSGUID = 'SQLVersionControl.VCS_SQL'

    declare @iReturn int
    exec @iReturn = master.dbo.sp_OACreat
GO
/*
**	This procedure returns the version number of the stored
**    procedures used by legacy versions of the Microsoft
**	Visual Database Tools.  Version is 7.0.00.
*/
create procedure dbo.dt_verstamp006
as
	select 7000

GO
/*
**	This procedure returns the version number of the stored
**    procedures used by the the Microsoft Visual Database Tools.
**	Version is 7.0.05.
*/
create procedure dbo.dt_verstamp007
as
	select 7005

GO
create proc dbo.dt_whocheckedout
        @chObjectType  char(4),
        @vchObjectName varchar(255),
        @vchLoginName  varchar(255),
        @vchPassword   varchar(255)

as

set nocount on

declare @iReturn int
declare @iObjectId int
s
GO
create proc dbo.dt_whocheckedout_u
        @chObjectType  char(4),
        @vchObjectName nvarchar(255),
        @vchLoginName  nvarchar(255),
        @vchPassword   nvarchar(255)

as

	-- This procedure should no longer be called;  dt_whochecke
GO

CREATE PROCEDURE FAQ_InsertBook 
    @title VARCHAR(255), 
    @pubdate DATETIME, 
    @synopsis VARCHAR(4000), 
    @salesCount INT 
AS 
BEGIN 
    SET NOCOUNT ON 
    DECLARE @newBookID INT 
    INSERT BOOKS 
    ( 
        title, 
      
GO
CREATE  Procedure pg_AdminChangePassL1
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N on U
GO




CREATE  Procedure pg_AdminChangePassL2
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N 
GO



CREATE  Procedure pg_ChangePassL1
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
--	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N o
GO



CREATE  Procedure pg_ChangePassL2
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N on 
GO
CREATE  Procedure pg_ResetPassL1
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N on U.JID =
GO


CREATE  Procedure sp_ChangePassword1
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(50) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N on
GO


CREATE  Procedure sp_ChangePassword2
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(255) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION
	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N o
GO


CREATE  Procedure sp_ResetPassword
	@MemberID INT,
	@OldPassword varchar(50) = '',
	@NewPassword varchar(255) = '',
	@ok int output
AS
    SET NOCOUNT ON
    BEGIN TRANSACTION

	    IF EXISTS (SELECT * FROM TB_User U inner join TB_Net2E N on 
GO


CREATE PROCEDURE usp_AddUser
(	
	@StrUserID varchar(25),
	@Password varchar(50),
	@SecPassword varchar(50),
	@FullName nvarchar(30), 
	@Question nvarchar(50),
	@Answer nvarchar(100),
	@Sex char(2), 
	@BirthDay datetime, 
	@Province nvarcha
GO
CREATE PROCEDURE usp_ChangeEmail
(	
	@StrUserID varchar(25),
	@OldEmail  varchar(50)='',
	@NewEmail  varchar(50)=''
)
AS
SET NOCOUNT ON
IF( not exists(SELECT Email from tb_user where Email = @NewEmail) and exists(Select JID From tb_user where St
GO


CREATE PROCEDURE usp_EditInfo
(
	@JID int,
	@StrUserID varchar(25),
	@Answer nvarchar(50),
	@FullName nvarchar(25), 
	@Sex char(2), 
	@BirthDay datetime, 
	@BirthPlace nvarchar(50), 
	@Email varchar(50),
	@Address nvarchar(100), 
	@Phone 
GO
CREATE TRIGGER [trgPaygate_Trans_Log] ON [dbo].[tb_paygate_trans] 
FOR INSERT
AS
BEGIN
Declare @cashValue int, @partnerID varchar(15), @trans_type varchar(25), @currMoney int, @trans_id int
Select @cashValue=moneyValue, @partnerID=account_id, @tran
GO
CREATE VIEW dbo.VIEW1
AS
SELECT     dbo.SK_PackageItem.ITEM_ID, dbo.SK_PackageItem.ITEM_NAME, a.Sum_Silk
FROM         dbo.SK_PackageItem INNER JOIN
                          (SELECT     PackageItemID, SUM(Silk_Own) AS Sum_Silk
                     

(108 rows affected)
