<?php
include_once('config.inc.php');
include_once('../vendor/autoload.php');
include_once('lib/STR.php');

//var $ReaderLoanNum;
//var $BookNO;
//var $action;			

/*****************************************************/

/***********取得POST之後的資料(ReaderID, BookNO*******/
if(isset($_POST["action"])){
	$action = $_POST["action"];
}elseif(isset($_GET["action"])){
	$action = $_GET["action"];
}

if(isset($_POST["ReaderID"])){
	$ReaderID	= $_POST["ReaderID"];
}elseif(isset($_GET["ReaderID"])){
	$ReaderID	= $_GET["ReaderID"];
}

$ReaderLoanNum	= isset($_POST["ReaderLoanNum"]) ?$_POST["ReaderLoanNum"] : '';
$BookNO			= isset($_POST["BookNO"]) ? $_POST["BookNO"] : '';
$action			= isset($_POST["action"]) ? $_POST["action"] : '';


/*****************************************************/
//判斷 $BookNO 是否為書籍編號，還是讀者編號

switch ($action){
	case "restore":
		$sql_loan  = sprintf("SELECT * FROM loan WHERE LoanStatus='1' And YearID='%s' ORDER BY RestoreDate DESC", $YearID);
		break;
	case "loan":
		// echo "action = loan <br />";
		if(isset($BookNO) and isset($ReaderID)){
			$BookNO = strtoupper($BookNO);
			if(preg_match('/^[1-9]{1}[0-9]{4,5}$/', $BookNO) || preg_match('/^[a-zA-Z]{2}[0-9]{4}$/', $BookNO) ){
				header("location:frmLoan.php?ReaderID=$BookNO&msg_loan=切換讀者為_$BookNO&action=reader" );
				//unset($BookNO);
			}
			if(book_loanable($BookNO)){
					$sqlLoanIns = sprintf("Insert Into loan(ReaderID, BookNO, BeginDate, OperatorID, YearID) VALUES ('%s', '%s', '%s', '%s', '%s')", strtolower($ReaderID), $BookNO, date("Y/m/d H:i:s"), $_SESSION['operator'], $YearID);
					$result = mysql_query($sqlLoanIns, $cnLibrary) or die(mysql_error());
					if($result){;
						$msg_loan = $BookNO . "借閱成功…";
					}else{
						$msg_loan =  $BookNO ."無法借閱…";
					}
				}else{
					$msg_loan = "$BookNo 非正確的書籍編號，請重新輸入！ ";
				}
				unset($BookNO);
		}else{
			$msg_loan = "輸入不完整，請重新輸入！！";
			unset($BookNO);unset($ReaderID);
		}
		
		
		break;
	case "reader":
		if(! reader_exist($ReaderID)){
			$msg_loan = "目前無此讀者編號，請重新輸入！";
			unset($ReaderID);
		}
		break;

	default:
}	//end switch


//若有輸入讀者編號，則取出該讀者今年度借閱的書籍
//並將借閱冊數指定給 $ReaderLoanNum 變數
if(isset($ReaderID)){
//	mysql_select_db("libraryutf");

	$ReaderID 			= strtoupper($ReaderID);

	$ReaderLoanNow 		= sprintf("Select L.ReaderID, L.BookNO, B.BookTitle, L.BeginDate FROM loan AS L INNER JOIN books AS B ON L.BookNO = B.BookNO  Where YearID='%s' AND L.ReaderID='%s' AND L.LoanStatus=0",$YearID, $ReaderID);
	
	$ReaderLoanResult 	= mysql_query($ReaderLoanNow, $cnLibrary) or die(mysql_error());
	$ReaderLoanNum 		= @mysql_num_rows($ReaderLoanResult);



	//if(ereg("^[1-9]{1}[0-9]{4,5}$", $ReaderID) ){
	if(preg_match('/^[1-9]{1}[0-9]{4,5}$/i', $ReaderID) ){
		$book_num_limit = $book_student_num_limit;
	//}elseif(ereg("^[tT]{1}[0-9]{4,5}$", $ReaderID)){
	}elseif(preg_match('/^[a-zA-Z]{2}[0-9]{4}$/i', $ReaderID)){
		$book_num_limit = $book_teacher_num_limit;
	}

}


/**
 * Database Connection 資料庫連線
 *
 */
function get_connection(){

	$DB = NewADOConnection(DB_TYPE);
	$DB->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	$DB->Execute('SET NAMES "UTF8"');
	$DB->SetFetchMode(ADODB_FETCH_BOTH);
	return $DB;
}




/**
 * 檢查使用者是否存在
 */
function reader_exist($readerid){
	global $cnLibrary, $db_library;
	$ret = 0;
	$sql_reader = sprintf("SELECT count(readerid) FROM readers WHERE ReaderID='%s'", $readerid);
	$result = mysql_query($sql_reader) or die(mysql_error());
	if(is_resource($result)){
		list($ret) = mysql_fetch_array($result);
	}
	return $ret;
}

/***********************************************
 * 檢查書籍是否可借閱
 * 1.先檢查該書籍編號是否存在
 * 2.檢查該書籍是否已被借閱
 * 若可借閱，則傳回 1，而否，則傳回 0
 ***********************************************/
function book_loanable($bookno){
	global $cnLibrary;
	global $db_library;
	if(book_exist($bookno)){
		if(book_loaned($bookno)){
			$ret = 0;
		}else{
			$ret = 1;
		}
	}else{
		$ret=0;
	}

	return $ret;

}


function reader_loan_table($schoolno = '', $loaned=0)
{
	$caption = "";



	if ($schoolno === '') return false;

	if($loaned){
		$stmt = "SELECT * FROM loanlist WHERE (end_date IS NOT NULL) AND reader_id = ?";
		$caption = "借閱歷史紀錄";
		$tr_header = "班級,學號,姓名,書籍標題,ISBN,借閱日期,歸還日期,管理員";
		$tr_args = "<tr>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
						</tr>";
	}
else{
		if($schoolno === 'all'){
			$stmt = "SELECT * FROM loanlist WHERE (end_date IS NULL) OR reader_id = ? ORDER BY Class1";
		}else{
			$stmt = "SELECT * FROM loanlist WHERE (end_date IS NULL) AND reader_id = ? ORDER BY begin_date DESC";
		}

	$caption = "借閱中書籍";
		$tr_header = "班級,學號,姓名,書籍標題,ISBN,借閱日期,歸還日期,管理員,動作";
		$tr_args = "<tr>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td style='text-align:center;'>%s</td>
							</tr>";
	}

	$db = get_connection();
	$rs = $db->Execute($stmt, array($schoolno));
	if ( $rs->RecordCount() == 0 ) return false;


	$str = " <caption>$caption</caption>";
	$str .= list_thead($tr_header);



	while ($row = $rs->FetchRow()) {
		if( $loaned ){
			$str .= sprintf($tr_args,
				$row["Class1"],
				$row["reader_id"],
				$row["name"],
				$row["book_title"],
				$row["book_isbn"],
				date("Y/m/d", strtotime($row["begin_date"])),
				date("Y/m/d", strtotime($row["end_date"])),
				$row["operator"]
			);
		}else{
			$str .= sprintf($tr_args,
				$row["Class1"],
				$row["reader_id"],
				$row["name"],
				$row["book_title"],
				$row["book_isbn"],
				date("Y/m/d", strtotime($row["begin_date"])),
				$row["end_date"],
				$row["operator"],
				sprintf("<a href='?action=restore&schoolno=%s&loan_id=%s'> <img src='images/ico_restore.png' title='還書'></a>", $schoolno, $row["id"])
			);
		}


	}


	return "<table class='booklist' style='margin-top:1.2em;'>$str</table>";
}


/**
 * 取得圖書查詢的結果
 * @param $value 傳入查詢關鍵字
 * @return $obj 傳回 ADOdb Recordset Object
 */
function get_booksearch($key = ''){

	if(empty($key)){
		return null;
	}

	$db = get_connection();

	$stmt = <<<EOD
					SELECT BookNo, BookTitle, Author, ISBN, Price, Place
					FROM
						books
					WHERE
						BookNO	like ?
						OR
						BookTitle like ?
						OR
						Author like ?
						OR
						ISBN  like ?
EOD;


	$db->SetFetchMode(ADODB_FETCH_BOTH);

//	$stmt = $db->prepare($stmt);

	$rs = $db->Execute($stmt,
		[$key.'%','%'.$key.'%', '%' . $key.'%', '%'.$key.'%']
	);

	return $rs;

}


/**
 * 查詢班級在某年某月借閱的本數
 * @param $classname 班級
 * @param $year	查詢年度(西元)
 * @param $month 查詢月份
 * @return mixed 傳回班級在某一個月份借閱的本數
 */
function get_class_month_loaned_num($classname, $year, $month){
	$stmt = <<<EOD
		SELECT Count(L.ReaderId) quantity
		FROM loan L
		LEFT JOIN readers R On L.ReaderID=R.ReaderID
		WHERE
			(BeginDate between '%s/%s/1' And '%s/%s/31')
			AND
			R.Class1='%s'
			AND
			R.IsWork=1

EOD;

	$stmt = sprintf($stmt, $year, $month, $year, $month, $classname);
//	echobr($stmt);
	$db = get_connection();
	if($rs = $db->Execute($stmt)){
		$row = $rs->FetchRow($rs);
		return $row['quantity'];
	}else{
		return $db->getMessages();
	}

}



/*
 * 檢查書籍是否存在
 *
 * 是-> 書數冊數值
 * 否-> 0
 */
function book_exist($bookno){
	global $cnLibrary;
	global $db_library;
	$sql_book = sprintf("SELECT count(BookNO) FROM books  WHERE BookNO='%s'", $bookno);
	$result = mysql_query($sql_book, $cnLibrary) or die(mysql_error());
	if(is_resource($result)){
		list($ret) = mysql_fetch_array($result);
	}else{
		$ret=0;
	}

	return $ret;
}


/**
 * 歸還書籍
 */
function restore_book($loan_id){

	if ( !is_numeric($loan_id) ) return false;

	$stmt = <<<EOD
		UPDATE loan
		SET
			end_date = now()
		WHERE
			id = ?
	    ;
EOD;

	$db = get_connection();
	if( $ret = $db->Execute($stmt, $loan_id) ){
		return $ret;
	}else{
		return false;
	}
}


/**
 * 還書列表
 */
function list_restore(){
	global $cnLibrary, $YearID;
	$stmt				= <<<EOD
					SELECT
						L.ReaderID,
						R.Name,
						R.class1,
						L.BookNO,
						B.BookTitle,
						L.BeginDate,
						B.Place
					FROM loan AS L
					INNER JOIN books AS B
						ON (L.BookNO = B.BookNO)
					INNER JOIN readers AS R
						ON (L.ReaderID = R.ReaderID)
					WHERE
						L.LoanStatus='1'
						AND
						L.RestoreDate >= '%s'
					ORDER BY L.RestoreDate DESC
EOD;
	$sql_list_restore 	= sprintf($stmt , date('Y/m/d'));
	$ret = mysql_query($sql_list_restore, $cnLibrary) or die(mysql_error());
	return $ret;
}

/**
 * 過期圖書列表
 *
 * @return 傳回 resource
 */
function book_expires(){
	global $cnLibrary, $YearID;

	$sql 	=<<<EOD
			SELECT
				L.ReaderID,
				R.name,
				R.class1,
				L.BeginDate,
				L.BookNO,
				B.booktitle
			FROM
				`Loan` AS L
			INNER JOIN
				Readers AS R
				ON
				L.ReaderID = R.ReaderID
			INNER JOIN
				Books AS B
				ON
				L.BookNo = B.bookno
			WHERE
				L.LoanStatus='0'
				AND
				L.BeginDate BETWEEN '2006/09/01' AND '2007/06/30'
			ORDER BY
				R.class1 ASC,
				L.BeginDate ASC
EOD;
		$ret = mysql_query($sql, $cnLibrary) or die(mysql_error());

	return $ret;
}

/**
 * 取出書籍號碼
 *
 * @param string $bookno
 * @return STRING
 */
function get_booktitle($bookno=""){
	global $cnLibrary;
	$booktitle = "";
	if(strlen($bookno)>0){
		$stmt = <<<EOD
				SELECT BookTitle FROM books 
				WHERE BookNo = '%s';
EOD;
		$stmt = sprintf($stmt, $bookno);
		$result	= mysql_query($stmt, $cnLibrary);
		while($record = mysql_fetch_array($result)){
			list($booktitle) = $record;
		}
	}
	return $booktitle;
}

/**
 * 取出今日借書清單
 * @return  mysql_result
 **/
function get_loan_today(){
	global $cnLibrary, $YearID;
//	$sql_get_loan_today = sprintf("SELECT L.ReaderID,R.Name,R.class1, L.BookNO, B.BookTitle, L.BeginDate, B.Place FROM loan As L INNER JOIN books AS B ON (L.BookNO = B.BookNO) INNER JOIN readers AS R ON (L.ReaderID = R.ReaderID) WHERE L.LoanStatus='0' AND date(L.BeginDate) ='%s' ORDER BY L.ID DESC", date('Y-m-d'));
	$sql_get_loan_today = sprintf("SELECT L.ReaderID,R.Name,R.class1, L.BookNO, B.BookTitle, L.BeginDate, B.Place FROM loan As L INNER JOIN books AS B ON (L.BookNO = B.BookNO) INNER JOIN readers AS R ON (L.ReaderID = R.ReaderID) WHERE date(L.BeginDate) ='%s' ORDER BY L.ID DESC", date('Y-m-d'));

//	die($sql_get_loan_today);
	$ret = mysql_query($sql_get_loan_today, $cnLibrary) or die( "讀取資料失敗，請與系統管理員聯絡！<br />" . mysql_error());

	return $ret;
}



/**
 * 檢查書籍是否外借中
 * 若已借出，則傳回 >0 的值，若否則傳 0
 */
function book_loaned($schoolno, $isbn){


	//檢查此書是否已借出(不檢查學年、學期)
	$stmt = "SELECT count(id) AS num FROM loanlist WHERE (end_date IS NULL) AND reader_id = ? AND book_isbn = ? ORDER BY begin_date DESC";
	$ret = 1;

	$db = get_connection();

	$rs = $db->Execute($stmt, array($schoolno, $isbn));
	if( $rs = $db->Execute($stmt, array($schoolno, $isbn)) ){

		if ( $row = $rs->FetchRow() ){
			return $row["num"];
		}
	}

	return $ret;
}


/**
 * 書籍目前借閱狀況
 */
function book_status($bookno){
	global $cnLibrary;
	$sqlbook = sprintf("SELECT count(BookNO) as num FROM loan WHERE BookNO='%s' and LoanStatus='0'", $bookno);
	$book_status = mysql_query($sqlbook, $cnLibrary) or die(mysql_error());
	if(is_resource($book_status)){
		list($ret) = mysql_fetch_array($book_status);
	}else{
		$ret =-1;
	}
	return $ret;
}

//檢查書籍是否存在
/**
 * 檢查輸入型態
 * 95001	=> 學生代碼
 * 10051	=> 學生代碼
 * B010051	=> 圖書代碼
 * @param STRING $str 輸入字串
 * @return  STRING
 */
function check_input($str){
	if(ereg("[1-9]{1}[0-9]{4,5}", $str)){
		$ret="reader";
	}elseif(ereg("[Bb][0-9]{6}", $str)){
		$ret="book";
	}elseif(ereg("[Aa][a-zA-Z][0-9]{4}", $str)){
		$ret="teacher";
	}else{
		$ret="";
	}
	return $ret;
}

/**
 * 檢查是否是登錄，若是，則無動作；若否，則轉向至登入畫面
 */
function check_logined(){
	if(!$_SESSION["logined"]){

//		die("<pre>" . var_dump() . "</pre>");
//		die($_SERVER['SCRIPT_NAME']);
	
//		header("location:frmLogin.php?msg=" . htmlspecialchars('請先登入，才有管理權限！') . "&forwardurl=" .  htmlspecialchars($_SERVER['SCRIPT_NAME']));
		header("location:frmLogin.php?msg=請先登入，才有管理權限！&forwardurl=" .  htmlspecialchars($_SERVER['SCRIPT_NAME']));
	}
}

function get_operator_level(){

	if($_SESSION["username"] != ''){
		$sql_get_level = sprintf("SELECT level FROM admin WHERE username='%s'", $_SESSION["username"]);

//		exit($sql_get_level);
		$result = mysql_query($sql_get_level, $cnLibrary) or die(mysql_error());
		if(is_resource($result)){
			list($ret) = mysql_fetch_array($result);
		}else{
			$ret=0;
		}
	}else{
		$ret = 0;
	}

	return $ret;
}

//取得讀者的中文姓名
//若有該讀者id，則傳回讀者姓名，若否，則傳回空字串
function get_reader($readerid){
	global $cnLibrary, $db_library;

	$sql_reader = sprintf("SELECT R.name FROM readers AS R WHERE R.ReaderID='%s'", $readerid);
	$result = mysql_query($sql_reader, $cnLibrary) or die(mysql_error());

	if(is_resource($result) and (mysql_num_rows($result)>0)){
		list($ret) = mysql_fetch_array($result);
	}else{
		$ret="";
	}
	return $ret;
}

//取得讀者的基本資料
//若有該讀者id，則傳回讀者姓名，若否，則傳回空字串
function get_reader_info($readerid){



	$stmt = "SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE R.ReaderID= ? ";

	$db = get_connection();



//	if(is_resource($result) and (mysql_num_rows($result)>0)){
	if( $rs = $db->Execute($stmt, $readerid) ){
		if( $row = $rs->FetchRow() )
			list($ret["readerid"],$ret["name"],$ret["class"],$ret["email"],$ret["birth"],$ret["sex"]) = $row;
	}else{
		$ret="";
	}
	return $ret;
}


// 取回整班學生的基本資料
function getinfo_readers($scope, $target){
	global $cnLibrary, $db_library;



	switch($scope){

		case "班級":
			$sql_readers = sprintf("SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE IsWork=1 And R.class1='%s' ORDER By R.Birth ", $target);
			break;
		case "學年":
			$sql_readers = sprintf("SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE IsWork=1 And Left(R.class1, 1)='%s' ORDER By R.Class1, R.ClassNO ", $target);
			break;
		case "全校":
			$sql_readers = sprintf("SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE IsWork=1 ORDER By R.Birth ");
			break;
	}

	
	// $sql_readers = sprintf("SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE IsWork=1 And R.class1='%s' ORDER By R.Birth ", $classname);
	$result = mysql_query($sql_readers, $cnLibrary) or die(mysql_error());

	if(is_resource($result) and (mysql_num_rows($result)>0)){
		for($i=0; $i < mysql_num_rows($result); $i++){
			$ret[$i]	= mysql_fetch_array($result);
		}

/*
		while ($reader = mysql_fetch_array($result))
		list($ret[]["readerid"],$ret[]["name"],$ret[]["class"],$ret[]["email"],$ret[]["birth"],$ret[]["sex"]) = $reader;
*/			
	}else{
		$ret="";
	}
	return $ret;	
}


// 取回目前校內帳號無密碼的學生資料
function get_readers_null_password(){
	global $cnLibrary, $db_library;

	$sql_readers = "SELECT R.readerid, R.name, R.class1, R.classno FROM readers AS R WHERE (`password` IS NULL OR `password`='') AND IsWork=1 AND LEFT(class1,1)>='3' ORDER By Class1,ClassNO";

	// $sql_readers = sprintf("SELECT R.ReaderID, R.name, R.Class1, R.ClassNO, R.Email, R.Birth, R.Sex FROM readers AS R WHERE IsWork=1 And R.class1='%s' ORDER By R.Birth ", $classname);
	$result = mysql_query($sql_readers, $cnLibrary) or die(mysql_error());

	if(is_resource($result) and (mysql_num_rows($result)>0)){
		for($i=0; $i < mysql_num_rows($result); $i++){
			$ret[$i]	= mysql_fetch_array($result);
		}
		
	}else{
		$ret="";
	}
	return $ret;	
}


// 取得操作者姓名
function get_operator($un){
	global $cnLibrary;
	global $db_library;
	$sql_operator = sprintf("SELECT cname FROM admin WHERE username='%s'  ", $un);
	$result = mysql_query($sql_operator, $cnLibrary) or die( mysql_error());
	if(is_resource($result)){
		list($ret) = mysql_fetch_array($result);
	}else{
		$ret = 0;
	}
	return $ret;
}

//若輸入的帳號密碼正確，則傳回陣列，若否，則傳回 0
function login($un, $pw){
	global $cnLibrary;
	global $db_library;
	$sql_login = sprintf("SELECT username, cname, level FROM admin WHERE username='%s' AND password='%s'", $un, $pw);
	$result = mysql_query($sql_login, $cnLibrary) or die(mysql_error());
	if(is_resource($result)){
		if(mysql_num_rows($result)>0){
			list($ret[0], $ret[1], $ret[2]) = mysql_fetch_array($result);
		}else{
			$ret=0;
		}
	}else{
		$ret = 0;
	}

	return $ret;
}	//end function


function addpercentmark($str){
	$str = "%" . $str . "%";
	return $str;
}




//**********取得網頁內容
function GetCurlPage ($pageSpec) {		//取得網路上某個連結網頁的內容
	if(function_exists('curl_init')){
		$agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
		$ref = "http://listbid.com/affil/";

		$ch = curl_init($pageSpec);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_REFERER, $ref);

		$tmp = curl_exec ($ch);
		curl_close ($ch);
		$ret = $tmp;
	}else{
		$ret = @implode('', @file ($pageSpec));
	}
	return $ret;
}



function GetEndDate($begindate){
	$ret = begindate;

	if(isset($begindate)){


	}

	return $ret;
}

function UpdateBookPlace($bookno, $place){
    global $cnLibrary;
	global $db_library;
	if(isset($bookno) && isset($place)){
	   $sql = sprintf("Update books SET place='%s' WHERE bookno='%s'", $place, $bookno);
	}

	if( mysql_query($sql) or $ret="更新時發生錯誤<br>" . mysql_error()){
      $ret = "書籍編號[<font color=blue> $bookno </font>] 已置於[<font color=red> $place </font> ]書櫃中";
 }else{
   $ret = "";
 };
	return $ret;
}


function get_this_year(){
	$ret	= "";
	$ret 	= (date("Y") + 0)-1911;
	if( date("n") < 8){
		$ret--;
	}
	return $ret;
}

/*function Booking($bookno){
	$ret = "";
	if(book_loanable($bookno)){
		$sqlLoanIns = sprintf("Insert Into loan(ReaderID, BookNO, BeginDate, OperatorID, YearID) VALUES ('%s', '%s', '%s', '%s', '%s')", $ReaderID, $BookNO, date("Y/m/d H:i:s"), $_SESSION['operator'], $YearID);
		$result = mysql_query($sqlLoanIns, $cnLibrary) or die(mysql_error());
		if($result){;
			$msg_loan = $bookno . "借閱成功…";
		}else{
			$msg_loan =  $bookno ."無法借閱…";
		}
	}
}
*/

/**
 * 合併Marc斷行的欄位
 *
 * @param  傳入分行的MARC文字檔內容
 * @return  傳回合併後的MARC陣列
 */
function mergeMarcItem($content){
	$arr = explode("\n", $content);
	$content = "";
	foreach($arr as $line){
		$str = (int)substr($line,0,3);
//		print gettype($str) ." type <br />";
		if($str >0 && $str < 999){
			$content .= $line . "\n";
		}else{
			$content = rtrim($content, "\n") . ltrim($line) . "\n";
		}
//		print $content . "<br />";
	}
	return $content;
}


/**
 * 將取得的資料存入陣列$book
 *
 * @param unknown_type $marc
 * @return unknown
 */
function parseMarcFormat($marc){
	foreach($marc as $line){
		//print 'substr($line,1,3) = ' .substr($line,0,3) . "<br />";
		switch(substr($line,0,3)){
			case '010':
				$line = substr($line,7);
				$items = preg_split('/\|[bd]/', $line);
				$book["ISBN"] 			= $items[0];
				$book["Binding"]	= $items[1];
				$book["Price"]			= $items[2];
//				$book["PublishType"]	= substr($items[1],1);
//				$book["Price"]			= substr($items[2],1);
				//$book["BookTitle"] = $line;
				break;
			case '200':
				$line = substr($line,7);
				$items = preg_split('/\|[fg]/', $line);
				$book["BookTitle"]		= str_replace('|e', ' ',$items[0]);
				$book["Author"]			= $items[1] . $items[2];
				break;
			case '205':
				$line = substr($line,7);
				$book["BookEdition"] = $line;
				break;
			case '210':
				$line = substr($line,7);
				$items = preg_split('/\|[cd]/', $line);
				$book["PublishPlace"] 	= $items[0];
				$book["Publisher"] 		= $items[1];
				$book["PublishYear"] 	= $items[2];
				break;
			case '215':
				$line = substr($line,7);
				$items = preg_split('/\|/', $line);
				foreach($items as $item){
					switch(substr($item,0,1)){
						case "c":
							$book["Chart"] 	= substr($item,1);
							break;

						case "d":
							$book["Height"] 	= substr($item,1);
							break;
					}
				}
				break;
			case '681':
				$line = substr($line,7);
				$items = preg_split('/\|[a-z]/', $line);
				$book["BookClassNum"] = $items[0];
				$book["AuthorNum"] = $items[1];
				if(count($items)>2){
					$book["VolumeNum"] = $items[2];
				}else{
					$book["VolumeNum"] = "";
				}
				break;
			case '606':
				$line = substr($line,7);
				$items = preg_split('/\|[a]/', $line);
				if(empty($book["Keyword"])){
					$book["Keyword"] = $items[1];
				}else{
					$book["Keyword"] = $book["Keyword"] . "," . $items[1];

				}
				break;
			case '225':
				$line = substr($line,7);
				$items = preg_split('/\|/', $line);
				$book["Series"] = $items[0];
				foreach($items as $item){
					switch(substr($item,0,1)){
						case "v":
							$book["VolumeNum"] 	= substr($item,1);
							break;
					}
				}
				break;
			case '200':
				$line = substr($line,7);
				$book["BookTitle"] = $line;
				break;
			case '200':
				$line = substr($line,7);
				$book["BookTitle"] = $line;
				break;
		}
//		$line = substr($line,6);
//		$book[] = $line;
	}

	return $book;

}

function get_book_marc($isbn){

	$book_query = fopen('http://192.83.186.170/search*cht/i' . $isbn . '/i' . $isbn . 'B/1%2C3%2C4%2CB/marc&FF=i' . $isbn . '&1%2C%2C2', "r");
	if($book_query){
		$content = stream_get_contents($book_query);
		fclose($book_query);
	}

	// $content = split('<div align="left">', $content);
	$content = explode('<div align="left">', $content);

	$content = mergeMarcItem($content[2]);
	$book_marc = $content;

	$book_marc = explode("\n", $book_marc);
	$book_marc = parseMarcFormat($book_marc);
	return $book_marc;
}

/**
 * ISBN 13 碼轉換成 10碼
 * @param unknown_type $isbn
 */
function isbn13to10($isbn) {
	$chksum		= 0;
	$chkresult	= 0;
	$chkcode	= array(1,3,1,3,1,3,1,3,1,3,1,3);
	if( (strlen($isbn) == 12) or (strlen($isbn) == 13) ){
		$isbn	= substr($isbn, 3, 9);
		
		for($i=0; $i < strlen($isbn); $i++){
			$chksum += substr($isbn,$i, 1) * (10 - $i);
		}
		$chkresult	= 11 -($chksum % 11);
		if( $chkresult == 10){
			$chkresult = "X";
		}elseif ( $chkresult == 11){
			$chkresult = "0";
		}
	}
	return ($isbn . $chkresult);
}



/**
自動產生密碼(數字)
**/
function password_autogen($len=4){
	for($i=0; $i < $len; $i++){
		$ret = $ret . mt_rand(0,9);
	}
	return $ret;
}


function get_monthname($month, $type=1)
{
	$ret = '';
	switch( $month ){
		case 1:
			$ret = '一';
			break;
		case 2:
			$ret = '二';
			break;
		case 3:
			$ret = '三';
			break;
		case 4:
			$ret = '四';
			break;
		case 5:
			$ret = '五';
			break;
		case 6:
			$ret = '六';
			break;
		case 7:
			$ret = '七';
			break;
		case 8:
			$ret = '八';
			break;
		case 9:
			$ret = '九';
			break;
		case 10:
			$ret = '十';
			break;
		case 11:
			$ret = '十<br />一';
			break;
		case 12:
			$ret = '十<br />二';
			break;
	}
	return $ret;
}

function num2chinese( $nums, $type = 1)
{
	$ret = '';
	$type_1 = '〇一二三四五六七八九十';
	$type_2 = '零壹貳參肆伍陸柒捌玖拾';

//	if($nums>10){
//		$arr = str_split($nums);
//		foreach ($arr as $item) {
//			$ret = $ret . num2chinese
//		}
//	}

	switch($type){
		case 1:
			$ret = mb_substr($type_1, $nums, 1);
			break;
		case 2:
			$ret = mb_substr($type_2, $nums, 1);
			break;
	}
	return $ret;
}


function list_messages(){

//	var $string_html = '';
	$lists = '';

	$db = get_connection();
	$stmt = "SELECT id, author, title, content, target, tag_id, created_date FROM msg_messages ORDER BY created_date";
	$db->SetFetchMode(ADODB_FETCH_BOTH);

//	$rs = $db->Execute($stmt,
//		[$key.'%','%'.$key.'%', '%' . $key.'%', '%'.$key.'%']
//	);

	$rs = $db->Execute($stmt);


	if($rs->RecordCount()){
		while ($row = $rs->FetchRow($rs)) {
			list($id, $author, $title, $content, $target, $tag_id, $created_date) = $row;
			$lists .= "<div><h3><img src='images/puce.gif' alt='puce' width='24' height='20' />$title</h3><div><ul><li><span>$created_date</span></li><li>$content</li></ul></div></div>";
		}
//		$lists = sprintf("")
	}


//	dump($rs);

	return $lists;
}


function list_thead($items, $properities='')
{
	$html_string = '';
	switch (gettype($items)) {
		case 'array':

			break;
		case 'object':
			$items = (array)$items;
			break;
		case 'string':
			$items = explode(',', $items);
			break;
	}
	foreach ($items as $item) {
		$html_string .= sprintf("<th>%s</th>", $item);
	}
	$html_string = sprintf("<thead $properities><tr>%s</tr></thead>", $html_string);
	return $html_string;
}


function echobr($str)
{
	print $str . "<br />";
}

function dump($var)
{
	print '<pre>';
	var_dump($var);
	print '</pre>';
}
